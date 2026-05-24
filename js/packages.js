function loadPackages() {
    let search = document.getElementById("search").value;
    let maxPrice = document.getElementById("maxPrice").value;
    let sort = document.getElementById("sort").value;

    // Show loading indicator
    const container = document.getElementById("packagesContainer");
    if (container) {
        container.innerHTML = '<div class="loading" style="text-align: center; padding: 40px;">Loading packages...</div>';
    }

    // Build URL with correct path
    let url = `/tripistry/traveller/get_packages.php?search=${encodeURIComponent(search)}&maxPrice=${encodeURIComponent(maxPrice)}&sort=${encodeURIComponent(sort)}`;
    
    console.log("Fetching packages from:", url); // Debug log

    let xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);

    xhr.onload = function () {
        console.log("Response status:", this.status); // Debug log
        console.log("Response text:", this.responseText.substring(0, 200)); // Debug log (first 200 chars)
        
        if (this.status == 200) {
            const container = document.getElementById("packagesContainer");
            if (container) {
                container.innerHTML = this.responseText;
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
            container.innerHTML = '<div class="empty-state">Error loading packages. Please check the console for details.</div>';
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

// Debounce function to prevent too many requests
function debounce(func, delay) {
    let timeout;
    return function() {
        clearTimeout(timeout);
        timeout = setTimeout(func, delay);
    };
}

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing filters..."); // Debug log
    
    // Initial load
    loadPackages();

    // Attach listeners with debounce for better performance
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
});