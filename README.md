# NGA Annual Dinner Ticketing System

A simple, secure, and user-friendly **online ticketing platform** for the **NGO-NGA Annual Dinner** event organized by the NGO community in Uganda.

Users can:
- Choose ticket types (Single, Couple, Alumni, Table for 6)
- Enter attendee names and phone numbers
- Pay securely online using **Pesapal** (MTN Mobile Money, Airtel Money, Visa/Mastercard, bank transfers)
- Receive an instant downloadable ticket with QR code after successful payment
- All purchases are automatically logged to a Google Sheet

Payments are settled directly to your linked bank account via Pesapal (after merchant approval).

## Features

- Responsive welcome page + ticket selection
- Dynamic form for multiple attendees (Couple = 2 people, Table = 6 people)
- Pesapal hosted payment page (redirect flow)
- IPN (Instant Payment Notification) for reliable payment confirmation
- Google Sheets integration (no database required)
- Ticket page with QR code, print & download options
- Full sandbox/demo testing support (no real money charged)

## Ticket Pricing (UGX)

| Ticket Type   | Price (UGX) | Attendees |
|---------------|-------------|-----------|
| Single        | 65,000      | 1         |
| Couple        | 120,000     | 2         |
| Alumni        | 100,000     | 1         |
| Table for 6   | 360,000     | 6         |

## Tech Stack

- **Backend**: PHP (plain, no frameworks)
- **Frontend**: HTML + CSS + Vanilla JavaScript
- **Payment Gateway**: Pesapal API v3 (JSON/REST)
- **Storage/Logging**: Google Sheets via Apps Script
- **QR Code Generation**: Google Chart API
- **Local Development**: XAMPP + ngrok (for IPN & callbacks)

## Prerequisites

- PHP 7.4+ with cURL enabled
- XAMPP / Laragon / any PHP server
- ngrok (free) – to expose localhost for Pesapal IPN
- Pesapal account (demo for testing, full merchant for live)
- Google account + new Google Sheet

## Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/nga-dinner-ticketing.git
cd nga-dinner-ticketing
2. Configure Pesapal (Sandbox / Demo Mode)

Go to https://demo.pesapal.com or https://developer.pesapal.com
Sign up / log in → create or use a demo merchant account
Get your Consumer Key and Consumer Secret (sandbox/demo keys)
In Pesapal dashboard → Settings → IPN → Add your IPN URL:texthttps://xxxx.ngrok-free.app/ipn_listener.php
Update process_payment.php with your demo keys:

PHP$consumer_key    = 'YOUR_DEMO_CONSUMER_KEY_HERE';
$consumer_secret = 'YOUR_DEMO_CONSUMER_SECRET_HERE';
$base_url        = 'https://cybqa.pesapal.com/pesapalv3'; // Sandbox
3. Set Up Google Sheets Logging

Create a new Google Sheet with these columns:
Date/Time | Full Name | Phone Number | Ticket Type | Price (UGX) | Ticket ID | Payment Status

Go to Extensions → Apps Script
Paste the provided Apps Script code (doPost + doGet functions)
Deploy → New deployment → Web app
Execute as: Me
Who has access: Anyone

Copy the /exec URL and update in process_payment.php:

PHP$google_script_url = 'https://script.google.com/macros/s/xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx/exec';
4. Local Testing (Sandbox / Demo Mode)

Start XAMPP Apache
Place project in htdocs/nga-tickets
Run ngrok:Bashngrok http 80Copy https://xxxx.ngrok-free.app
Update URLs:
$callback_url in process_payment.php
IPN URL in Pesapal dashboard

Open: http://localhost/nga-tickets/ or ngrok URL

5. Test Payments (Demo Mode – No Real Money)

Use Pesapal sandbox test credentials:
MTN/Airtel: Test Ugandan phone (e.g. 0772123456) + simulator PIN/OTP
Card: Pesapal test card details (see developer docs)

After successful test payment:
Redirected to ticket.php with real names/phone/type
Google Sheet updated via IPN to "Completed"


6. Switching to Live (Production)

Apply for a full merchant account at https://www.pesapal.com
Complete verification (ID, business details, bank account)
Receive live Consumer Key and Consumer Secret
Update process_payment.php:

PHP$base_url        = 'https://pay.pesapal.com/v3'; // Live
$consumer_key    = 'YOUR_LIVE_CONSUMER_KEY';
$consumer_secret = 'YOUR_LIVE_CONSUMER_SECRET';

Update IPN URL in live Pesapal dashboard to your real domain
Deploy to real hosting (Hostinger, DigitalOcean, etc.)
Use HTTPS (required for live – free via Let's Encrypt or hosting)
Test with a small real payment first to confirm money reaches your bank

Folder Structure
textnga-dinner-ticketing/
├── index.php              # Welcome page
├── tickets.php            # Ticket selection + dynamic form
├── process_payment.php    # Pesapal payment initiation
├── ticket.php             # Ticket display + QR + download/print
├── ipn_listener.php       # Pesapal IPN handler
├── styles.css             # Basic styling
├── scripts.js             # Dynamic form logic
└── README.md
Security & Best Practices

Never commit API keys to GitHub (use .env or server config in production)
Validate IPN in ipn_listener.php (check merchant reference & status)
Use HTTPS in production
Add CSRF tokens to forms
Monitor Pesapal dashboard for settlements & disputes
