console.log("reviews.js loaded successfully");
console.log("TRAVELER_ID =", TRAVELER_ID);

var agencies = document.getElementById("agencies");
var packages = document.getElementById("packages");
var a_search = document.getElementById("search_agency");
var p_search = document.getElementById("search_package");
var modal = document.getElementById("rateModal");
var currentItemId = null;
var currentItemType = null;
var currentRating = 5;

function getAgencies() {
    console.log("Fetching agencies...");
    agencies.innerHTML = '<div style="color:rgba(255,255,255,0.5);">Loading agencies...</div>';
    
    var xhr = new XMLHttpRequest();
    var data = {
        "type": "getAgency",
        "search": a_search.value
    };
    
    xhr.open("POST", "/tripistry/traveller/get_review_data.php", true);
    xhr.setRequestHeader("Content-Type", "application/json");
    
    xhr.onload = function() {
        console.log("Agencies Status:", xhr.status);
        console.log("Agencies Response:", xhr.responseText);
        
        if (xhr.status === 200) {
            try {
                var result = JSON.parse(xhr.responseText);
                console.log("Agencies Parsed:", result);
                
                agencies.innerHTML = "";
                if (result.data && result.data.length > 0) {
                    for (var i = 0; i < result.data.length; i++) {
                        agencies.innerHTML += '<div class="agency-item" id="a_' + result.data[i].agency_ID + '">' +
                            '<span class="agency-name">🏢 ' + (result.data[i].agency_name || 'Agency ' + result.data[i].agency_ID) + '</span>' +
                            '<a href="#" class="btn-rate" data-type="agency" data-id="' + result.data[i].agency_ID + '">Rate Agency</a>' +
                            '</div>';
                    }
                    getAgencyRatings();
                    attachRateButtons();
                } else {
                    agencies.innerHTML = '<div style="color:rgba(255,255,255,0.5);">No agencies found.</div>';
                }
            } catch(e) {
                console.error("Parse error:", e);
                agencies.innerHTML = '<div style="color:red;">Error parsing response</div>';
            }
        } else {
            agencies.innerHTML = '<div style="color:red;">Error: ' + xhr.status + '</div>';
        }
    };
    
    xhr.onerror = function() {
        console.error("Request failed");
        agencies.innerHTML = '<div style="color:red;">Request failed</div>';
    };
    
    xhr.send(JSON.stringify(data));
}

function getAgencyRatings() {
    var req = new XMLHttpRequest();
    var data = {
        "type": "getAgencyRating",
        "traveler_id": TRAVELER_ID
    };
    
    req.onreadystatechange = function() {
        if (req.readyState == 4 && req.status == 200) {
            var result = JSON.parse(req.responseText);
            for (var i = 0; i < result.data.length; i++) {
                var div = document.getElementById("a_" + result.data[i].agency_ID);
                if (div) {
                    var score = Math.floor(result.data[i].rating_score);
                    var starsSpan = document.createElement('span');
                    starsSpan.className = 'stars';
                    var starsText = '';
                    for (var j = 0; j < score; j++) {
                        starsText += '★';
                    }
                    starsSpan.innerHTML = ' ' + starsText;
                    div.querySelector('.agency-name').appendChild(starsSpan);
                }
            }
        }
    };
    req.open("POST", "/tripistry/traveller/get_review_data.php", true);
    req.setRequestHeader("Content-Type", "application/json");
    req.send(JSON.stringify(data));
}

function getPackages() {
    console.log("Fetching packages...");
    packages.innerHTML = '<div style="color:rgba(255,255,255,0.5);">Loading packages...</div>';
    var req = new XMLHttpRequest();
    var data = {
        "type": "getPackages",
        "search": p_search.value
    };
    
    req.onreadystatechange = function() {
        if (req.readyState == 4) {
            console.log("Packages response status:", req.status);
            console.log("Packages response:", req.responseText);
            if (req.status == 200) {
                try {
                    var result = JSON.parse(req.responseText);
                    console.log("Packages parsed:", result);
                    packages.innerHTML = "";
                    if (result.data && result.data.length > 0) {
                        for (var i = 0; i < result.data.length; i++) {
                            packages.innerHTML += '<div class="package-item" id="p_' + result.data[i].package_ID + '">' +
                                '<span class="package-name">📦 ' + (result.data[i].package_name || 'Package ' + result.data[i].package_ID) + '</span>' +
                                '<a href="#" class="btn-rate" data-type="package" data-id="' + result.data[i].package_ID + '">Rate Package</a>' +
                                '</div>';
                        }
                        getPackageRatings();
                        attachRateButtons();
                    } else {
                        packages.innerHTML = '<div style="color:rgba(255,255,255,0.5);">No packages found.</div>';
                        console.log("No packages data returned from server");
                    }
                } catch(e) {
                    console.error("Parse error:", e);
                    packages.innerHTML = '<div style="color:red;">Error: ' + e.message + '</div>';
                }
            } else {
                packages.innerHTML = '<div style="color:red;">HTTP Error: ' + req.status + '</div>';
            }
        }
    };
    req.open("POST", "/tripistry/traveller/get_review_data.php", true);
    req.setRequestHeader("Content-Type", "application/json");
    req.send(JSON.stringify(data));
}

function getPackageRatings() {
    var req = new XMLHttpRequest();
    var data = {
        "type": "getPackageRating",
        "traveler_id": TRAVELER_ID
    };
    
    req.onreadystatechange = function() {
        if (req.readyState == 4 && req.status == 200) {
            var result = JSON.parse(req.responseText);
            for (var i = 0; i < result.data.length; i++) {
                var div = document.getElementById("p_" + result.data[i].package_ID);
                if (div) {
                    var score = Math.floor(result.data[i].rating_score);
                    var starsSpan = document.createElement('span');
                    starsSpan.className = 'stars';
                    var starsText = '';
                    for (var j = 0; j < score; j++) {
                        starsText += '★';
                    }
                    starsSpan.innerHTML = ' ' + starsText;
                    div.querySelector('.package-name').appendChild(starsSpan);
                }
            }
        }
    };
    req.open("POST", "/tripistry/traveller/get_review_data.php", true);
    req.setRequestHeader("Content-Type", "application/json");
    req.send(JSON.stringify(data));
}

function attachRateButtons() {
    var buttons = document.querySelectorAll(".btn-rate");
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener("click", function(e) {
            e.preventDefault();
            currentItemId = this.getAttribute("data-id");
            currentItemType = this.getAttribute("data-type");
            var itemName = this.parentElement.querySelector('.agency-name, .package-name').innerText;
            document.getElementById("modalContent").innerHTML = "Rate " + currentItemType + ": " + itemName;
            modal.showModal();
        });
    }
}

// Modal star rating
var modalStars = document.querySelectorAll("#modalStars span");
for (var i = 0; i < modalStars.length; i++) {
    modalStars[i].addEventListener("click", function() {
        currentRating = parseInt(this.getAttribute("data-value"));
        for (var j = 0; j < modalStars.length; j++) {
            if (parseInt(modalStars[j].getAttribute("data-value")) <= currentRating) {
                modalStars[j].classList.add("active");
            } else {
                modalStars[j].classList.remove("active");
            }
        }
    });
}

document.getElementById("submitReview").addEventListener("click", function() {
    var reviewText = document.getElementById("modalReview").value;
    
    if (!reviewText.trim()) {
        alert("Please write a review before submitting.");
        return;
    }
    
    console.log("Submitting review for:", currentItemType, currentItemId, "Rating:", currentRating);
    
    var saveReq = new XMLHttpRequest();
    var saveData = {
        "type": "saveReview",
        "item_type": currentItemType,
        "item_id": currentItemId,
        "rating": currentRating,
        "description": reviewText,
        "traveler_id": TRAVELER_ID
    };
    
    saveReq.onreadystatechange = function() {
        if (saveReq.readyState == 4) {
            console.log("Save response status:", saveReq.status);
            console.log("Save response text:", saveReq.responseText);
            
            if (saveReq.status == 200) {
                try {
                    var result = JSON.parse(saveReq.responseText);
                    if (result.success) {
                        alert("Review submitted successfully!");
                        modal.close();
                        document.getElementById("modalReview").value = "";
                        for (var j = 0; j < modalStars.length; j++) {
                            modalStars[j].classList.remove("active");
                        }
                        currentRating = 5;
                        for (var j = 0; j < 5; j++) {
                            modalStars[j].classList.add("active");
                        }
                        if (currentItemType === 'package') {
                            setTimeout(function() {
                                window.location.href = "/tripistry/traveller/package_view.php?id=" + currentItemId;
                            }, 1000);
                        } else if (currentItemType === 'agency') {
                            // Refresh agencies to show updated rating
                            getAgencies();
                        }
                    } else {
                        alert("Error: " + (result.error || "Failed to save review"));
                    }
                } catch(e) {
                    console.error("Parse error:", e);
                    alert("Error saving review");
                }
            } else {
                alert("Error saving review. Status: " + saveReq.status);
            }
        }
    };
    
    saveReq.open("POST", "/tripistry/traveller/get_review_data.php", true);
    saveReq.setRequestHeader("Content-Type", "application/json");
    saveReq.send(JSON.stringify(saveData));
});

document.getElementById("closeModal").addEventListener("click", function() {
    modal.close();
});

a_search.addEventListener("input", getAgencies);
p_search.addEventListener("input", getPackages);
getAgencies();
getPackages();