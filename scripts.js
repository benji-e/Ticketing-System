document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.ticket-card');
    const form = document.getElementById('details-form');
    const fieldsContainer = document.getElementById('people-fields');
    const ticketTypeInput = document.getElementById('ticket_type');
    const priceInput = document.getElementById('price');

    cards.forEach(card => {
        card.addEventListener('click', () => {
            const type = card.dataset.type;
            const price = card.dataset.price;
            const people = parseInt(card.dataset.people);

            ticketTypeInput.value = type;
            priceInput.value = price;

            fieldsContainer.innerHTML = ''; // Clear previous fields
            for (let i = 1; i <= people; i++) {
                fieldsContainer.innerHTML += `
                    <label>Person ${i} Full Name:</label>
                    <input type="text" name="full_name_${i}" required>
                    <label>Person ${i} Phone Number:</label>
                    <input type="tel" name="phone_${i}" required>
                `;
            }
            form.style.display = 'block';
            form.classList.add('fade-in'); // Animation
        });
    });
});