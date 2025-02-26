import asyncio
from fastapi import FastAPI, BackgroundTasks, HTTPException
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
from scipy.sparse import csr_matrix, hstack
from pydantic import BaseModel


app = FastAPI()
vectorizer = joblib.load('feb retrained model/vectorizer_retrained_final.joblib')
model = joblib.load('feb retrained model/random_forest_model_retrained_final.joblib')
print("Model expected features:", model.n_features_in_)
class_names = np.array(['Safe Email', 'Phishing Email'])
SCOPES = ['https://www.googleapis.com/auth/gmail.readonly']

# Define request body model
class EmailRequest(BaseModel):
    body: str

# Connect to db
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

# Store fetched emails in the db
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

# Store predictions in db
def store_prediction_in_db(email_id, prediction):
    conn = get_db_connection()
    if conn is not None:
        try:
            cursor = conn.cursor()
            
            # Check if the email_id exists in fetched_emails table
            check_query = "SELECT COUNT(*) FROM fetched_emails WHERE email_id = %s"
            cursor.execute(check_query, (email_id,))
            exists = cursor.fetchone()[0] > 0
            
            if exists:
                # Insert the prediction into email_predictions table
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

# Background task to fetch emails and run predictions
async def fetch_and_predict():
    while True:
        try:
            # Fetch new emails
            service = get_gmail_service()
            emails = fetch_emails(service)

            store_emails_in_db(emails)

            # Run predictions on fetched emails
            for email in emails:
                prediction = predict_email(email['body'])
                store_prediction_in_db(email['id'], prediction)

        except Exception as e:
            print(f"Error in background task: {e}")

        # Wait for 10mins before fetching again
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
            flow = InstalledAppFlow.from_client_secrets_file('credentials.json', SCOPES)
            creds = flow.run_local_server(port=8000)
        with open('token.json', 'w') as token:
            token.write(creds.to_json())
    return build('gmail', 'v1', credentials=creds)

# Fetch emails from Gmail
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

# Prediction function using the model
def predict_email(email_body):
    processed_input = transform_email_features(email_body)
    print("Model expected features:", model.n_features_in_)
    print(f"Processed input shape: {processed_input.shape}")  # Debugging
    prediction = model.predict(processed_input)
    
    return class_names[prediction[0]]  # Return 'Safe Email' or 'malicious Email' based on prediction

def transform_email_features(email_body):
    # Cleaning the email body
    processed_body = preprocess_text(email_body)
    
    # Vectorize the cleaned text using the pre-trained vectorizer
    email_tfidf = vectorizer.transform([processed_body])
    print(f"TF-IDF features shape: {email_tfidf.shape}")

    # Extract additional features: word count and malicious content
    additional_features = np.array([
        word_count(processed_body),
        malicious_content(processed_body)
    ]).reshape(1, -1)  # Reshape for a single sample
    print(f"Additional features shape: {additional_features.shape}")

    # Convert additional features to sparse matrix
    additional_features_sparse = csr_matrix(additional_features)
    
    # Combine the TF-IDF features with the additional features
    combined_features = hstack([email_tfidf, additional_features_sparse])
    print(f"Combined features shape: {combined_features.shape}") 

    return combined_features

# Preprocess the text by removing unwanted characters
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
    
    # Return 1 if malicious content is detected, 0 otherwise
    return int(malicious_score > 0)


# Start the background task when the application starts
@app.on_event("startup")
async def startup_event():
    asyncio.create_task(fetch_and_predict())

@app.get("/")
async def read_root():
    return {"message": "Welcome to Inbox Guard!"}

@app.get("/test-spam")
async def test_spam():
    # Test with a safe email
    test_email = """
    Subject: Important: Your Account Has Been Compromised

    Dear User,

    Urgent!

    We have detected unauthorized access to your account. To secure your account, please click the link below and reset your password immediately:

    http://phishy-site.com/reset-password

    If you do not reset your password within 24 hours, your account will be permanently locked.

    Thank you,
    The Security Team

    """
    prediction = predict_email(test_email)
    return {"prediction": prediction}

@app.post("/predict")
async def predict_email_api(email_request: EmailRequest):
    try:
        # Predict classification
        prediction = predict_email(email_request.body)
        return {"predicted_class": prediction}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))