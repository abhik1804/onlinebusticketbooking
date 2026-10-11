/**
 * Shreenathji Travels - City Search & Autocomplete
 */

document.addEventListener('DOMContentLoaded', function () {
  initCityAutocomplete('from_city_input', 'from_city_id', 'from_suggestions');
  initCityAutocomplete('to_city_input', 'to_city_id', 'to_suggestions');

  // Prevent past dates
  const dateInput = document.getElementById('journey_date_input');
  if (dateInput) {
    // Current date in IST format YYYY-MM-DD
    const today = new Date().toLocaleDateString('en-CA');
    dateInput.min = today;
    if (!dateInput.value) {
      dateInput.value = today;
    }
  }

  // City Swap Button
  const swapBtn = document.getElementById('btn-swap-cities');
  if (swapBtn) {
    swapBtn.addEventListener('click', function () {
      const fromInput = document.getElementById('from_city_input');
      const fromId = document.getElementById('from_city_id');
      const toInput = document.getElementById('to_city_input');
      const toId = document.getElementById('to_city_id');

      if (fromInput && toInput && fromId && toId) {
        const tempName = fromInput.value;
        const tempId = fromId.value;

        fromInput.value = toInput.value;
        fromId.value = toId.value;

        toInput.value = tempName;
        toId.value = tempId;

        // Visual feedback
        swapBtn.style.transform = 'rotate(180deg) scale(1.1)';
        setTimeout(() => {
          swapBtn.style.transform = '';
        }, 300);
      }
    });
  }

  // Quick Route Chips click handler
  document.querySelectorAll('.quick-route-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      const fromCity = this.getAttribute('data-from');
      const toCity = this.getAttribute('data-to');
      const fromId = this.getAttribute('data-from-id');
      const toId = this.getAttribute('data-to-id');

      const fromInput = document.getElementById('from_city_input');
      const fromIdInput = document.getElementById('from_city_id');
      const toInput = document.getElementById('to_city_input');
      const toIdInput = document.getElementById('to_city_id');

      if (fromInput && toInput) {
        fromInput.value = fromCity;
        toInput.value = toCity;
        if (fromIdInput) fromIdInput.value = fromId || '';
        if (toIdInput) toIdInput.value = toId || '';

        // Auto submit search if desired or focus on search button
        const searchBtn = document.getElementById('btn-search-buses');
        if (searchBtn) {
          searchBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }
    });
  });

  // Search Form Validation
  const searchForm = document.getElementById('bus-search-form');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      const fromInput = document.getElementById('from_city_input');
      const toInput = document.getElementById('to_city_input');
      const fromId = document.getElementById('from_city_id');
      const toId = document.getElementById('to_city_id');
      const dateVal = document.getElementById('journey_date_input')?.value;

      if (!fromInput.value.trim() || !fromId.value) {
        e.preventDefault();
        showToast('Please select a valid departure city from suggestions', 'warning');
        fromInput.focus();
        return;
      }

      if (!toInput.value.trim() || !toId.value) {
        e.preventDefault();
        showToast('Please select a valid destination city from suggestions', 'warning');
        toInput.focus();
        return;
      }

      if (fromId.value === toId.value || fromInput.value.trim().toLowerCase() === toInput.value.trim().toLowerCase()) {
        e.preventDefault();
        showToast('Departure and destination cities cannot be the same', 'danger');
        toInput.focus();
        return;
      }

      const todayStr = new Date().toLocaleDateString('en-CA');
      if (dateVal && dateVal < todayStr) {
        e.preventDefault();
        showToast('Journey date cannot be in the past', 'danger');
        return;
      }
    });
  }
});

/**
 * Autocomplete Dropdown Logic with Debounce
 */
function initCityAutocomplete(inputId, hiddenId, dropdownId) {
  const input = document.getElementById(inputId);
  const hidden = document.getElementById(hiddenId);
  const dropdown = document.getElementById(dropdownId);
  if (!input || !dropdown) return;

  let debounceTimer = null;
  let activeIndex = -1;

  input.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    const query = this.value.trim();
    hidden.value = ''; // Reset ID until valid selection

    if (query.length < 1) {
      dropdown.classList.remove('show');
      dropdown.innerHTML = '';
      return;
    }

    debounceTimer = setTimeout(() => {
      fetchCities(query, dropdown, input, hidden);
    }, 200);
  });

  input.addEventListener('focus', function () {
    if (this.value.trim().length >= 1) {
      fetchCities(this.value.trim(), dropdown, input, hidden);
    }
  });

  // Keyboard navigation: Arrow Up, Arrow Down, Enter, Escape
  input.addEventListener('keydown', function (e) {
    const items = dropdown.querySelectorAll('.city-suggestion-item');
    if (!dropdown.classList.contains('show') || items.length === 0) return;

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIndex = (activeIndex + 1) % items.length;
      updateActiveItem(items, activeIndex);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIndex = (activeIndex - 1 + items.length) % items.length;
      updateActiveItem(items, activeIndex);
    } else if (e.key === 'Enter') {
      if (activeIndex >= 0 && items[activeIndex]) {
        e.preventDefault();
        items[activeIndex].click();
      }
    } else if (e.key === 'Escape') {
      dropdown.classList.remove('show');
    }
  });

  // Close dropdown on click outside
  document.addEventListener('click', function (e) {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.remove('show');
    }
  });
}

function updateActiveItem(items, index) {
  items.forEach((item, i) => {
    if (i === index) {
      item.classList.add('active');
      item.scrollIntoView({ block: 'nearest' });
    } else {
      item.classList.remove('active');
    }
  });
}

function fetchCities(query, dropdown, input, hidden) {
  const url = window.ST_BASE_URL ? `${window.ST_BASE_URL}/api/cities.php?q=${encodeURIComponent(query)}` : `api/cities.php?q=${encodeURIComponent(query)}`;

  fetch(url)
    .then(res => res.json())
    .then(data => {
      dropdown.innerHTML = '';
      if (!data || !data.cities || data.cities.length === 0) {
        dropdown.innerHTML = '<div style="padding: 12px; color: #94a3b8; font-size: 0.85rem; text-align: center;">No matching cities found</div>';
        dropdown.classList.add('show');
        return;
      }

      data.cities.forEach(city => {
        const item = document.createElement('div');
        item.className = 'city-suggestion-item';
        item.innerHTML = `
          <div>
            <div class="city-name">${city.name}</div>
            <div class="city-state"><i class="fas fa-map-marker-alt text-muted me-1"></i>${city.state}</div>
          </div>
          ${city.is_popular == 1 ? '<span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Popular</span>' : ''}
        `;

        item.addEventListener('click', function () {
          input.value = city.name;
          hidden.value = city.id;
          dropdown.classList.remove('show');
        });

        dropdown.appendChild(item);
      });
      dropdown.classList.add('show');
    })
    .catch(err => {
      console.error('Error fetching cities:', err);
    });
}
