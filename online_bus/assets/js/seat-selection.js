/**
 * Shreenathji Travels - Interactive Seat Selection & Locking Logic
 */

document.addEventListener('DOMContentLoaded', function () {
  initSeatSelection();
});

let selectedSeats = [];
let maxSeatsLimit = 6;
let lockCountdownInterval = null;

function initSeatSelection() {
  const container = document.getElementById('bus-seat-map-wrapper');
  if (!container) return;

  // Deck Switcher
  const deckBtns = document.querySelectorAll('.deck-pill-btn');
  deckBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      deckBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const targetDeck = this.getAttribute('data-deck');

      document.querySelectorAll('.seats-deck-container').forEach(deckEl => {
        if (deckEl.getAttribute('data-deck') === targetDeck) {
          deckEl.classList.add('active');
        } else {
          deckEl.classList.remove('active');
        }
      });
    });
  });

  // Seat Click Handler
  document.querySelectorAll('.bus-seat-unit.status-available').forEach(seatEl => {
    seatEl.addEventListener('click', function () {
      const seatNum = this.getAttribute('data-seat');
      const fare = parseFloat(this.getAttribute('data-fare') || 0);

      const existingIndex = selectedSeats.findIndex(s => s.seatNumber === seatNum);

      if (existingIndex > -1) {
        // Deselect
        selectedSeats.splice(existingIndex, 1);
        this.classList.remove('status-selected');
      } else {
        // Check Limit
        if (selectedSeats.length >= maxSeatsLimit) {
          showToast(`You can select a maximum of ${maxSeatsLimit} seats per booking.`, 'warning');
          return;
        }

        selectedSeats.push({
          seatNumber: seatNum,
          fare: fare,
          type: this.getAttribute('data-type'),
          deck: this.getAttribute('data-deck'),
          isLadies: this.getAttribute('data-ladies') === '1'
        });
        this.classList.add('status-selected');
      }

      updateSeatSummaryDrawer();
    });
  });

  // Proceed Button Click
  const btnProceed = document.getElementById('btn-proceed-passengers');
  if (btnProceed) {
    btnProceed.addEventListener('click', function () {
      if (selectedSeats.length === 0) {
        showToast('Please select at least one seat to proceed.', 'warning');
        return;
      }

      const scheduleId = this.getAttribute('data-schedule-id');
      const baseAppUrl = window.ST_BASE_URL || '';

      // Lock seats on backend via AJAX
      btnProceed.disabled = true;
      btnProceed.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Locking Seats...';

      fetch(`${baseAppUrl}/api/lock_seats.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          schedule_id: scheduleId,
          seats: selectedSeats.map(s => s.seatNumber)
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast('Seats locked successfully! Please fill in passenger details.', 'success');
          // Store selected seats in sessionStorage for passenger form
          sessionStorage.setItem('st_selected_seats', JSON.stringify(selectedSeats));
          sessionStorage.setItem('st_lock_expires_at', data.expires_at);

          // Redirect to passenger details page
          setTimeout(() => {
            window.location.href = `${baseAppUrl}/booking/passenger_details.php?schedule_id=${scheduleId}`;
          }, 400);
        } else {
          btnProceed.disabled = false;
          btnProceed.innerHTML = 'Proceed to Passenger Details <i class="fas fa-arrow-right ms-2"></i>';
          showToast(data.message || 'Selected seat(s) were just booked by another user. Please choose another seat.', 'danger');
          // Reload seat availability
          setTimeout(() => window.location.reload(), 1500);
        }
      })
      .catch(err => {
        console.error(err);
        btnProceed.disabled = false;
        btnProceed.innerHTML = 'Proceed to Passenger Details <i class="fas fa-arrow-right ms-2"></i>';
        showToast('Network error while locking seats. Please try again.', 'danger');
      });
    });
  }
}

function updateSeatSummaryDrawer() {
  const drawer = document.getElementById('seat-summary-drawer');
  const countBadge = document.getElementById('selected-seats-count');
  const seatsListEl = document.getElementById('selected-seats-list');
  const totalFareEl = document.getElementById('selected-seats-total');
  const btnProceed = document.getElementById('btn-proceed-passengers');

  if (!drawer) return;

  if (selectedSeats.length > 0) {
    drawer.style.display = 'flex';
    if (countBadge) countBadge.textContent = selectedSeats.length;
    if (seatsListEl) seatsListEl.textContent = selectedSeats.map(s => s.seatNumber).join(', ');

    const total = selectedSeats.reduce((acc, curr) => acc + curr.fare, 0);
    if (totalFareEl) totalFareEl.textContent = '₹' + total.toFixed(2);
    if (btnProceed) btnProceed.disabled = false;
  } else {
    drawer.style.display = 'none';
    if (btnProceed) btnProceed.disabled = true;
  }
}

/**
 * Seat Lock Countdown Timer used in Booking Pages
 */
function startSeatLockTimer(expiresAtTimestamp, onExpireCallback) {
  const timerDisplay = document.getElementById('lock-countdown-timer');
  if (!timerDisplay) return;

  clearInterval(lockCountdownInterval);

  function tick() {
    const now = Math.floor(Date.now() / 1000);
    const remaining = expiresAtTimestamp - now;

    if (remaining <= 0) {
      clearInterval(lockCountdownInterval);
      timerDisplay.textContent = '00:00';
      if (typeof onExpireCallback === 'function') {
        onExpireCallback();
      } else {
        alert('Your temporary seat lock has expired. Please select your seats again.');
        window.location.href = window.ST_BASE_URL || '/online_bus';
      }
      return;
    }

    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;
    timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
  }

  tick();
  lockCountdownInterval = setInterval(tick, 1000);
}
