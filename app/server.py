import asyncio
from fastapi import FastAPI, BackgroundTasks
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
from googleapiclient.discovery import build
import joblib
import numpy as np
import os
import mysql.connector
from mysql.connector import Error
from datetime import datetime
import re
import numpy as npx
from scipy.sparse import csr_matrix, hstack

app = FastAPI()

vectorizer = joblib.load('retrained model/vectorizer.joblib')
model = joblib.load('retrained model/random_forest_model.joblib')
class_names = np.array(['Safe Email', 'Phishing Email'])
SCOPES = ['https://www.googleapis.com/auth/gmail.readonly']
REDIRECT_URI = "http://localhost:8000/"

flow = InstalledAppFlow.from_client_secrets_file(
    'credentials.json', SCOPES, redirect_uri=REDIRECT_URI
)


# connect to db
def get_db_connection():
    try:
        conn = mysql.connector.connect(
            host='localhost',
            database='inboxguard',
            user='root',
            password=''
        )
        if conn.is_connected():
            return conn
    except Error as e:
        print(f"Error connecting to MySQL: {e}")
        return None

# store fetched emails in the db
def store_emails_in_db(emails):
    conn = get_db_connection()
    if conn is not None:
        cursor = conn.cursor()
        for email in emails:
            query = "INSERT INTO fetched_emails (email_id, sender, subject, body, email_date) VALUES (%s, %s, %s, %s, %s)"
            cursor.execute(query, (email['email_id'], email['sender'], email['subject'], email['body'], email['email_date']))
        conn.commit()
        cursor.close()
        conn.close()

# store predictions in db
def store_prediction_in_db(email_id, prediction):
    conn = get_db_connection()
    if conn is not None:
        try:
            cursor = conn.cursor()
            
            # check if the email_id exists in fetched_emails table
            check_query = "SELECT COUNT(*) FROM fetched_emails WHERE email_id = %s"
            cursor.execute(check_query, (email_id,))
            exists = cursor.fetchone()[0] > 0
            
            if exists:
                # insert the prediction into email_predictions table
                query = "INSERT INTO email_predictions (email_id, classification, predicted_at) VALUES (%s, %s, %s)"
                cursor.execute(query, (email_id, prediction, datetime.now()))
                conn.commit()
            else:
                print(f"Email ID {email_id} does not exist in fetched_emails table.")
                
        except Error as e:
            print(f"Error inserting prediction into DB: {e}")
        finally:
            cursor.close()
            conn.close()


# background task to fetch emails and run predictions
async def fetch_and_predict():
    while True:
        try:
            # fetch new emails
            service = get_gmail_service()
            emails = fetch_emails(service)

            store_emails_in_db(emails)

            # run predictions on fetched emails
            for email in emails:
                prediction = predict_email(email['body'])
                store_prediction_in_db(email['id'], prediction)

        except Exception as e:
            print(f"Error in background task: {e}")

        # wait for 10mins before fetching again
        await asyncio.sleep(600)


# Gmail service authentication
def get_gmail_service():
    creds = None
    if os.path.exists('token.json'):
        creds = Credentials.from_authorized_user_file('token.json', SCOPES)
    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            creds.refresh(Request())
        else:
            flow = InstalledAppFlow.from_client_secrets_file(
                'credentials.json', SCOPES, redirect_uri=REDIRECT_URI
            )
            creds = flow.run_local_server(port=0)
        with open('token.json', 'w') as token:
            token.write(creds.to_json())
    return build('gmail', 'v1', credentials=creds)

# fetch emails from Gmail
def fetch_emails(service):
    results = service.users().messages().list(userId='me', labelIds=['INBOX'], maxResults=10).execute()
    messages = results.get('messages', [])
    emails = []

    for message in messages:
        msg = service.users().messages().get(userId='me', id=message['id']).execute()
        email_data = {
            'id': message['id'],
            'subject': msg['payload']['headers'][0]['value'], 
            'body': msg['snippet'],  
            'received_time': msg['internalDate'] 
        }
        emails.append(email_data)

    return emails

# prediction function using the model
def predict_email(email_body):
    processed_input = transform_email_features(email_body)
    prediction = model.predict(processed_input)
    
    # check for malicious content after preprocessing
    if malicious_content(email_body) > 0: 
        return class_names[1]  # return 'malicious' if malicious content is detected
    
    return class_names[prediction[0]]  # return 'Safe Email' or 'malicious Email' based on prediction

def transform_email_features(email_body):
    # cleaning
    processed_body = preprocess_text(email_body)
    
    # vectorize the cleaned text
    email_tfidf = vectorizer.transform([processed_body])
    
    # extract additional features
    additional_features = np.array([
        word_count(processed_body),
        malicious_content(processed_body)
    ]).reshape(1, -1)  # reshape for a single sample
    
    # convert additional features to sparse matrix
    additional_features_sparse = csr_matrix(additional_features)
    
    # combine the TF-IDF features with the additional features
    combined_features = hstack([email_tfidf, additional_features_sparse])
    return combined_features

# preprocess the text by removing unwanted characters
def preprocess_text(text):
    text = re.sub(r'[^a-zA-Z\s]', '', text)  
    text = re.sub(r'\s+', ' ', text)         
    return text.lower()                      

def word_count(text):
    return len(text.split())

def malicious_content(text):
    text = text.lower()
    malicious_phrases = ['urgent', 'click here', 'limited time', 'verify account', 
                         'password reset', 'act now', 'suspicious activity', 
                         'bank', 'invoice', 'free', 'credit card']
    
    phrase_count = sum(phrase in text for phrase in malicious_phrases)
    html_tag_pattern = r'<[^>]+>'
    contains_html = int(bool(re.search(html_tag_pattern, text)))

    malicious_score = phrase_count + contains_html
    
    # return 1 if malicious content is detected, 0 otherwise
    return int(malicious_score > 0)


# start the background task when the application starts
@app.on_event("startup")
async def startup_event():
    asyncio.create_task(fetch_and_predict())

@app.get("/")
async def read_root():
    return {"message": "Welcome to Inbox Guard!"}
