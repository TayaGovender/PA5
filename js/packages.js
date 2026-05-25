let selectedPackages = [];

// Load saved selections from localStorage
function loadSavedSelections() {
    const saved = localStorage.getItem('comparePackages');
    if (saved) {
        selectedPackages = JSON.parse(saved);
        updateCompareButton();
        // Check the checkboxes that were previously selected
        document.querySelectorAll('.pkg-checkbox').forEach(checkbox => {
            if (selectedPackages.includes(checkbox.value)) {
                checkbox.checked = true;
            }
        });
    }
}

function loadPackages() {
    let search = document.getElementById("search").value;
    let maxPrice = document.getElementById("maxPrice").value;
    let sort = document.getElementById("sort").value;

    const container = document.getElementById("packagesContainer");
    if (container) {
        container.innerHTML = '<div class="loading" style="text-align: center; padding: 40px;">Loading packages...</div>';
    }

    let url = `/tripistry/traveller/get_packages.php?search=${encodeURIComponent(search)}&maxPrice=${encodeURIComponent(maxPrice)}&sort=${encodeURIComponent(sort)}`;
    
    console.log("Fetching packages from:", url);

    let xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);

    xhr.onload = function () {
        console.log("Response status:", this.status);
        
        if (this.status == 200) {
            const container = document.getElementById("packagesContainer");
            if (container) {
                container.innerHTML = this.responseText;
                attachCompareListeners();
                loadSavedSelections(); // Restore saved selections after loading
            }
        } else {
            const container = document.getElementById("packagesContainer");
            if (container) {
                container.innerHTML = '<div class="empty-state">Error loading packages. Status: ' + this.status + '</div>';
            }
        }
    };
    
    xhr.onerror = function() {
        console.error("AJAX request failed");
        const container = document.getElementById("packagesContainer");
        if (container) {
            container.innerHTML = '<div class="empty-state">Error loading packages. Please check the console.</div>';
        }
    };
    
    xhr.send();
}

function resetFilters() {
    document.getElementById("search").value = "";
    document.getElementById("maxPrice").value = "";
    document.getElementById("sort").value = "";
    loadPackages();
}

function updateCompareButton() {
    const compareBtn = document.getElementById('compareBtn');
    if (selectedPackages.length >= 2) {
        compareBtn.style.display = 'inline-block';
        compareBtn.innerHTML = `📊 Compare (${selectedPackages.length})`;
    } else {
        compareBtn.style.display = 'none';
    }
    // Save to localStorage
    localStorage.setItem('comparePackages', JSON.stringify(selectedPackages));
}

function attachCompareListeners() {
    const checkboxes = document.querySelectorAll('.pkg-checkbox');
    checkboxes.forEach(checkbox => {
        // Remove existing listener to avoid duplicates
        checkbox.removeEventListener('change', checkbox.changeHandler);
        
        // Create and store the handler
        checkbox.changeHandler = function() {
            const pkgId = this.value;
            
            if (this.checked) {
                if (selectedPackages.length < 4) {
                    if (!selectedPackages.includes(pkgId)) {
                        selectedPackages.push(pkgId);
                    }
                } else {
                    alert('You can compare up to 4 packages at a time');
                    this.checked = false;
                }
            } else {
                selectedPackages = selectedPackages.filter(id => id !== pkgId);
            }
            updateCompareButton();
        };
        
        checkbox.addEventListener('change', checkbox.changeHandler);
    });
}

// Clear compare selection (call this when needed)
function clearCompareSelection() {
    selectedPackages = [];
    localStorage.removeItem('comparePackages');
    updateCompareButton();
    // Uncheck all checkboxes
    document.querySelectorAll('.pkg-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function debounce(func, delay) {
    let timeout;
    return function() {
        clearTimeout(timeout);
        timeout = setTimeout(func, delay);
    };
}

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing filters...");
    loadPackages();

    const searchInput = document.getElementById("search");
    const maxPriceInput = document.getElementById("maxPrice");
    const sortSelect = document.getElementById("sort");
    
    if (searchInput) {
        searchInput.addEventListener("keyup", debounce(loadPackages, 500));
    }
    if (maxPriceInput) {
        maxPriceInput.addEventListener("keyup", debounce(loadPackages, 500));
        maxPriceInput.addEventListener("change", loadPackages);
    }
    if (sortSelect) {
        sortSelect.addEventListener("change", loadPackages);
    }
    
    const compareBtn = document.getElementById('compareBtn');
    if (compareBtn) {
        compareBtn.addEventListener('click', function() {
            if (selectedPackages.length >= 2) {
                window.location.href = `compare.php?ids=${selectedPackages.join(',')}`;
            } else {
                alert('Please select at least 2 packages to compare');
            }
        });
    }
});