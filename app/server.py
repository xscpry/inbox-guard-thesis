from fastapi import FastAPI
import joblib
import numpy as np
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
from googleapiclient.discovery import build
import json
import os

# Load the pre-trained model
model = joblib.load('app/Random_Forest_model.joblib')

# Class names for the model predictions
class_names = np.array(['Safe Email', 'Phishing Email'])

# Create FastAPI instance
app = FastAPI()

# If modifying these SCOPES, delete the file token.json.
SCOPES = ['https://www.googleapis.com/auth/gmail.readonly']

def get_gmail_service():
    creds = None
    # The file token.json stores the user's access and refresh tokens.
    if os.path.exists('token.json'):
        creds = Credentials.from_authorized_user_file('token.json', SCOPES)
    # If there are no (valid) credentials available, let the user log in.
    if not creds or not creds.valid:
        if creds and creds.expired and creds.refresh_token:
            creds.refresh(Request())
        else:
            flow = InstalledAppFlow.from_client_secrets_file(
                'credentials.json', SCOPES)
            creds = flow.run_local_server(port=0)
        # Save the credentials for the next run
        with open('token.json', 'w') as token:
            token.write(creds.to_json())

    return build('gmail', 'v1', credentials=creds)

def fetch_emails(service):
    results = service.users().messages().list(userId='me', labelIds=['INBOX'], maxResults=10).execute()
    messages = results.get('messages', [])

    emails = []
    if not messages:
        print('No messages found.')
    else:
        for message in messages:
            msg = service.users().messages().get(userId='me', id=message['id']).execute()
            email_data = msg['payload']['headers']
            subject = next(item['value'] for item in email_data if item['name'] == 'Subject')
            sender = next(item['value'] for item in email_data if item['name'] == 'From')
            body = msg['snippet']  # You can retrieve the body in more detail if needed
            
            emails.append({
                'id': message['id'],
                'subject': subject,
                'sender': sender,
                'body': body,
            })
    return emails

@app.get('/')
def read_root():
    return {'message': 'Phishing Email Detection API'}

@app.post('/predict')
def predict(data: dict):
    features = np.array(data['features']).reshape(1, -1)  # Reshape for a single prediction
    prediction = model.predict(features)  # Get the prediction from the model
    class_name = class_names[prediction][0]  # Map the prediction to class name
    return {'predicted_class': class_name}  # Return the predicted class

@app.get('/fetch-emails')
def get_emails():
    service = get_gmail_service()
    emails = fetch_emails(service)
    return {'emails': emails}