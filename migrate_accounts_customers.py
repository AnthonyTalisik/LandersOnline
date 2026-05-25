import firebase_admin
from firebase_admin import credentials, firestore, auth
import csv
import os
import json

# --- Configuration ---
SERVICE_ACCOUNT_KEY_PATH = 'C:/xampp/htdocs/LandersOnline/serviceAccountKey.json'
ACCOUNTS_CUSTOMERS_CSV_PATH = 'C:/xampp/htdocs/LandersOnline/accounts_customers.csv'

# --- Initialize Firebase Admin SDK (same as before) ---
if not os.path.exists(SERVICE_ACCOUNT_KEY_PATH):
    print(f"Error: Service account key not found at {SERVICE_ACCOUNT_KEY_PATH}")
    exit()

try:
    cred = credentials.Certificate(SERVICE_ACCOUNT_KEY_PATH)
    firebase_admin.initialize_app(cred)
    db = firestore.client()
    print("Firebase Admin SDK initialized successfully.")
except Exception as e:
    print(f"Error initializing Firebase Admin SDK: {e}")
    exit()

# Helper function to safely get and convert CSV values, handling '\N' and empty strings
def get_csv_value(row_data, key, target_type=str):
    value = row_data.get(key)
    if value == '\\N' or value is None or value == '':
        return None
    try:
        if target_type == int:
            return int(value)
        elif target_type == float:
            return float(value)
        return str(value) # Default to string
    except ValueError:
        print(f"Warning: Could not convert '{value}' for key '{key}' to {target_type.__name__}. Setting to None.")
        return None


def migrate_accounts_customers(csv_file_path):
    print(f"Starting migration for accounts and customers from {csv_file_path}...")
    try:
        with open(csv_file_path, mode='r', encoding='utf-8') as file:
            reader = csv.DictReader(file)
            firestore_batch = db.batch()
            auth_created_count = 0 # Count for newly created auth users
            auth_found_count = 0 # Count for existing auth users found
            firestore_processed_count = 0 # Count for Firestore documents created/updated
            
            mysql_acct_id_to_firebase_uid = {}

            for i, row in enumerate(reader):
                email = get_csv_value(row, 'Acct_Email')
                password_from_mysql = get_csv_value(row, 'Acct_Password')
                mysql_acct_id = get_csv_value(row, 'Acct_Id', target_type=int)

                if not email:
                    print(f"Skipping row {i+2} due to missing email.")
                    continue
                
                # --- EXTRACT THESE VALUES HERE, BEFORE THE AUTH BLOCK ---
                first_name = get_csv_value(row, 'Cust_FName')
                last_name = get_csv_value(row, 'Cust_LName')
                
                # Determine must_change_pw from CSV, handling potential None
                must_change_pw_str = get_csv_value(row, 'Acct_MustChangePw')
                must_change_pw = bool(get_csv_value(row, 'Acct_MustChangePw', target_type=int)) if must_change_pw_str is not None else False


                uid = None
                
                # --- Try to get user from Firebase Auth, if not found, create ---
                try:
                    user_record = auth.get_user_by_email(email)
                    uid = user_record.uid
                    auth_found_count += 1
                    # print(f"User {email} already exists in Firebase Auth. UID: {uid}") # Uncomment for verbose logging
                    
                except auth.UserNotFoundError:
                    # User does not exist, so create a new one
                    try:
                        user_properties = {
                            'email': email,
                            'email_verified': False,
                            'disabled': get_csv_value(row, 'Acct_Status') == 'inactive',
                        }

                        display_name_parts = [name for name in [first_name, last_name] if name]
                        user_properties['display_name'] = ' '.join(display_name_parts) if display_name_parts else email

                        # Password handling logic (same as before)
                        if must_change_pw:
                            user_properties['password'] = password_from_mysql # Use original plain text as temp password
                        else:
                            user_properties['password'] = 'TemporaryPassword123!@#' # Use a strong temporary password

                        user_record = auth.create_user(**user_properties)
                        uid = user_record.uid
                        auth_created_count += 1
                        # print(f"Created new Firebase Auth user for {email}. UID: {uid}") # Uncomment for verbose logging

                    except Exception as e:
                        print(f"ERROR: Could not create new Firebase Auth user for {email} (MySQL Acct_Id: {mysql_acct_id}). Error: {e}")
                        continue # Skip to next row if Auth user creation failed

                # If we have a UID (either existing or newly created), proceed with Firestore document
                if uid:
                    firestore_user_data = {
                        'email': email,
                        'role': get_csv_value(row, 'Acct_Role'),
                        'status': get_csv_value(row, 'Acct_Status'),
                        'mustChangePw': must_change_pw, # This flag will need to be checked by your app logic
                        'firstName': first_name, # Use extracted first_name
                        'lastName': last_name,   # Use extracted last_name
                        'phone': get_csv_value(row, 'Cust_Phone'),
                        'mysqlAcctId': mysql_acct_id,
                        'mysqlCustId': get_csv_value(row, 'Cust_Id', target_type=int),
                        'createdAt': firestore.SERVER_TIMESTAMP
                    }
                    
                    user_doc_ref = db.collection('users').document(uid)
                    # Using set(..., merge=True) is safer here to update existing documents without overwriting
                    # fields that might have been added by the app itself.
                    firestore_batch.set(user_doc_ref, firestore_user_data, merge=True) 
                    firestore_processed_count += 1
                    
                    mysql_acct_id_to_firebase_uid[str(mysql_acct_id)] = uid # Store mapping

                if firestore_processed_count % 500 == 0 and firestore_processed_count > 0:
                    firestore_batch.commit()
                    firestore_batch = db.batch()
                    print(f"Auth users created: {auth_created_count}, found: {auth_found_count}. Firestore user profiles processed: {firestore_processed_count}...")

            if firestore_processed_count > 0:
                firestore_batch.commit()

            print(f"Migration complete: {auth_created_count} Firebase Auth users created, {auth_found_count} existing users processed. {firestore_processed_count} Firestore user profiles migrated/updated.")
            
            with open('mysql_acct_id_to_firebase_uid_map.json', 'w') as f:
                json.dump(mysql_acct_id_to_firebase_uid, f, indent=4)
            print("MySQL Acct_Id to Firebase UID mapping saved to mysql_acct_id_to_firebase_uid_map.json")

    except FileNotFoundError:
        print(f"Error: CSV file not found at {csv_file_path}")
    except Exception as e:
        print(f"An error occurred during account/customer migration: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    migrate_accounts_customers(ACCOUNTS_CUSTOMERS_CSV_PATH)
