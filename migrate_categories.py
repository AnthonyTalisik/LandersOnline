import firebase_admin
from firebase_admin import credentials, firestore
import csv
import os

# --- Configuration ---
# Updated path to your renamed service account key JSON file
SERVICE_ACCOUNT_KEY_PATH = 'C:/xampp/htdocs/LandersOnline/serviceAccountKey.json'
CATEGORIES_CSV_PATH = 'C:/xampp/htdocs/LandersOnline/categories.csv' # This is your CSV file!

# Ensure the service account key path is correct
if not os.path.exists(SERVICE_ACCOUNT_KEY_PATH):
    print(f"Error: Service account key not found at {SERVICE_ACCOUNT_KEY_PATH}")
    print("Please download your service account key from Firebase Console -> Project settings -> Service accounts -> Generate new private key.")
    exit()

# --- Initialize Firebase Admin SDK ---
try:
    cred = credentials.Certificate(SERVICE_ACCOUNT_KEY_PATH)
    firebase_admin.initialize_app(cred)
    db = firestore.client()
    print("Firebase Admin SDK initialized successfully.")
except Exception as e:
    print(f"Error initializing Firebase Admin SDK: {e}")
    exit()

def migrate_categories(csv_file_path):
    print(f"Starting migration for categories from {csv_file_path}...")
    try:
        with open(csv_file_path, mode='r', encoding='utf-8') as file:
            reader = csv.DictReader(file)
            batch = db.batch()
            doc_count = 0

            for row in reader:
                # Firestore document ID: Using Cat_Id from MySQL
                firestore_doc_id = str(row['Cat_Id'])

                # Prepare the data for the Firestore document
                category_data = {
                    'name': row['Cat_Name'],
                    'status': row['Cat_Status'],
                    'mysqlId': int(row['Cat_Id']) # Keep original MySQL ID for reference if needed
                }

                # Add to batch for writing to 'categories' collection
                doc_ref = db.collection('categories').document(firestore_doc_id)
                batch.set(doc_ref, category_data)
                doc_count += 1

                # Commit batch every 500 documents to avoid memory issues and respect limits
                if doc_count % 500 == 0:
                    batch.commit()
                    batch = db.batch() # Start a new batch
                    print(f"Committed {doc_count} categories...")

            # Commit any remaining documents in the last batch
            if doc_count % 500 != 0 or doc_count == 0:
                batch.commit()
            print(f"Successfully migrated {doc_count} categories to Firestore.")

    except FileNotFoundError:
        print(f"Error: CSV file not found at {csv_file_path}")
    except Exception as e:
        print(f"An error occurred during category migration: {e}")

# --- Execute Migration ---
if __name__ == "__main__":
    migrate_categories(CATEGORIES_CSV_PATH)