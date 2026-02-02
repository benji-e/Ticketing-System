<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buy Tickets</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="tickets-container">
        <h1>Select Your Ticket</h1>
        <div class="ticket-options">
            <div class="ticket-card" data-type="Single" data-price="65000" data-people="1">
                <h2>Single</h2>
                <p>65,000 UGX</p>
                <p>1 Person</p>
            </div>
            <div class="ticket-card" data-type="Couple" data-price="120000" data-people="2">
                <h2>Couple</h2>
                <p>120,000 UGX</p>
                <p>2 People</p>
            </div>
            <div class="ticket-card" data-type="Alumni" data-price="100000" data-people="1">
                <h2>Alumni</h2>
                <p>100,000 UGX</p>
                <p>1 Person</p>
            </div>
            <div class="ticket-card" data-type="Table for 6" data-price="360000" data-people="6">
                <h2>Table for 6</h2>
                <p>360,000 UGX</p>
                <p>6 People</p>
            </div>
        </div>
        <form id="details-form" action="process_payment.php" method="POST" style="display:none;">
            <h2>Enter Details</h2>
            <div id="people-fields"></div>
            <input type="hidden" name="ticket_type" id="ticket_type">
            <input type="hidden" name="price" id="price">
            <button type="submit" class="buy-btn">Proceed to Payment</button>
        </form>
    </div>
    <script src="scripts.js"></script>
</body>
</html>