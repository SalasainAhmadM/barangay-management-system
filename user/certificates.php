<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
  header("Location: ../index.php?auth=error");
  exit();
}
?>
<!DOCTYPE html>

<html lang="en">

<head>
  <?php include '../components/header_links.php'; ?>
  <?php include '../components/user_side_header.php'; ?>
</head>

<body>
  <?php include '../components/navbar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Page Header -->
    <div class="page-header">
      <h1>Certificates & Requests</h1>
      <p>Apply for barangay certificates, permits, and track your requests</p>
    </div>

    <!-- Tabs Container -->
    <div class="tabs-container">
      <div class="tabs">
        <button class="tab-btn active" onclick="openTab(event, 'certificates')">
          <i class="fas fa-certificate"></i> Certificates
        </button>
        <button class="tab-btn" onclick="openTab(event, 'permits')">
          <i class="fas fa-file-contract"></i> Permits
        </button>
        <button class="tab-btn" onclick="openTab(event, 'tracking')">
          <i class="fas fa-tasks"></i> Track Requests
        </button>
      </div>

      <!-- Certificates Tab -->
      <div id="certificates" class="tab-content active">
        <div class="info-banner">
          <i class="fas fa-info-circle"></i>
          <div class="info-banner-content">
            <h4>Important Information</h4>
            <p>Processing time for certificates is typically 3-5 business days. Please ensure all required information
              is accurate before submitting.</p>
          </div>
        </div>

        <div class="certificates-grid">
          <div class="certificate-card" onclick="applyCertificate('residency')">
            <div class="certificate-icon">
              <i class="fas fa-home"></i>
            </div>
            <h3>Certificate of Residency</h3>
            <p>Proof of residence within the barangay. Required for various transactions.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 3-5 days</span>
              <span><i class="fas fa-peso-sign"></i> ₱50.00</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>

          <div class="certificate-card" onclick="applyCertificate('indigency')">
            <div class="certificate-icon">
              <i class="fas fa-hand-holding-heart"></i>
            </div>
            <h3>Certificate of Indigency</h3>
            <p>For financial assistance purposes, medical aid, and educational support.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 3-5 days</span>
              <span><i class="fas fa-peso-sign"></i> Free</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>

          <div class="certificate-card" onclick="applyCertificate('clearance')">
            <div class="certificate-icon">
              <i class="fas fa-id-card"></i>
            </div>
            <h3>Barangay Clearance</h3>
            <p>Required for employment, business permits, and other legal purposes.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 5-7 days</span>
              <span><i class="fas fa-peso-sign"></i> ₱100.00</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>

          <div class="certificate-card" onclick="applyCertificate('good-moral')">
            <div class="certificate-icon">
              <i class="fas fa-award"></i>
            </div>
            <h3>Good Moral Character</h3>
            <p>Certification of good standing in the community. Often required for jobs.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 3-5 days</span>
              <span><i class="fas fa-peso-sign"></i> ₱50.00</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>

          <div class="certificate-card" onclick="applyCertificate('low-income')">
            <div class="certificate-icon">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <h3>Low Income Certificate</h3>
            <p>For government assistance programs and subsidies.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 3-5 days</span>
              <span><i class="fas fa-peso-sign"></i> Free</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>

          <div class="certificate-card" onclick="applyCertificate('cohabitation')">
            <div class="certificate-icon">
              <i class="fas fa-heart"></i>
            </div>
            <h3>Certificate of Cohabitation</h3>
            <p>For couples living together without formal marriage.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 5-7 days</span>
              <span><i class="fas fa-peso-sign"></i> ₱75.00</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>
        </div>
      </div>

      <!-- Permits Tab -->
      <div id="permits" class="tab-content">
        <div class="info-banner">
          <i class="fas fa-info-circle"></i>
          <div class="info-banner-content">
            <h4>Permit Requirements</h4>
            <p>Most permits require additional documentation and inspection. Processing time varies depending on permit
              type.</p>
          </div>
        </div>

        <div class="certificates-grid">
          <div class="certificate-card" onclick="applyPermit('business')">
            <div class="certificate-icon">
              <i class="fas fa-store"></i>
            </div>
            <h3>Business Permit</h3>
            <p>Required to operate a business within the barangay jurisdiction.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 7-10 days</span>
              <span><i class="fas fa-peso-sign"></i> ₱500.00+</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>

          <div class="certificate-card" onclick="applyPermit('construction')">
            <div class="certificate-icon">
              <i class="fas fa-hard-hat"></i>
            </div>
            <h3>Construction Permit</h3>
            <p>For building, renovation, or construction projects in the barangay.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 10-14 days</span>
              <span><i class="fas fa-peso-sign"></i> ₱300.00+</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>

          <div class="certificate-card" onclick="applyPermit('fencing')">
            <div class="certificate-icon">
              <i class="fas fa-border-style"></i>
            </div>
            <h3>Fencing Permit</h3>
            <p>Required for installing or repairing fences and boundary walls.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 5-7 days</span>
              <span><i class="fas fa-peso-sign"></i> ₱200.00</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>

          <div class="certificate-card" onclick="applyPermit('excavation')">
            <div class="certificate-icon">
              <i class="fas fa-digging"></i>
            </div>
            <h3>Excavation Permit</h3>
            <p>For digging or excavation activities within barangay roads.</p>
            <div class="certificate-meta">
              <span><i class="fas fa-clock"></i> 7-10 days</span>
              <span><i class="fas fa-peso-sign"></i> ₱250.00</span>
            </div>
            <button class="apply-btn">Apply Now</button>
          </div>
        </div>
      </div>

      <!-- Tracking Tab -->
      <div id="tracking" class="tab-content">
        <div class="status-filters">
          <button class="filter-btn active" onclick="filterRequests('all')">All Requests</button>
          <button class="filter-btn" onclick="filterRequests('pending')">Pending</button>
          <button class="filter-btn" onclick="filterRequests('processing')">Processing</button>
          <button class="filter-btn" onclick="filterRequests('approved')">Approved</button>
          <button class="filter-btn" onclick="filterRequests('ready')">Ready for Pickup</button>
          <button class="filter-btn" onclick="filterRequests('rejected')">Rejected</button>
        </div>

        <div class="requests-list">
          <!-- Request Card - Pending -->
          <div class="request-card pending">
            <div class="request-header">
              <div class="request-title">
                <div class="request-icon-small">
                  <i class="fas fa-id-card"></i>
                </div>
                <div class="request-title-text">
                  <h3>Barangay Clearance</h3>
                  <p>Request ID: #BR-2025-001234</p>
                </div>
              </div>
              <span class="status-badge-large pending">Pending</span>
            </div>
            <div class="request-details">
              <div class="detail-item">
                <span class="detail-label">Submitted Date</span>
                <span class="detail-value">Sept 28, 2025</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Purpose</span>
                <span class="detail-value">Employment</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Fee</span>
                <span class="detail-value">₱100.00</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Expected Date</span>
                <span class="detail-value">Oct 5, 2025</span>
              </div>
            </div>
            <div class="request-actions">
              <button class="action-btn-small secondary">
                <i class="fas fa-eye"></i> View Details
              </button>
              <button class="action-btn-small danger">
                <i class="fas fa-times"></i> Cancel Request
              </button>
            </div>
          </div>

          <!-- Request Card - Processing -->
          <div class="request-card processing">
            <div class="request-header">
              <div class="request-title">
                <div class="request-icon-small">
                  <i class="fas fa-home"></i>
                </div>
                <div class="request-title-text">
                  <h3>Certificate of Residency</h3>
                  <p>Request ID: #BR-2025-001198</p>
                </div>
              </div>
              <span class="status-badge-large processing">Processing</span>
            </div>
            <div class="request-details">
              <div class="detail-item">
                <span class="detail-label">Submitted Date</span>
                <span class="detail-value">Sept 25, 2025</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Purpose</span>
                <span class="detail-value">Government ID</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Fee</span>
                <span class="detail-value">₱50.00</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Expected Date</span>
                <span class="detail-value">Oct 2, 2025</span>
              </div>
            </div>
            <div class="request-actions">
              <button class="action-btn-small secondary">
                <i class="fas fa-eye"></i> View Details
              </button>
            </div>
          </div>

          <!-- Request Card - Ready -->
          <div class="request-card ready">
            <div class="request-header">
              <div class="request-title">
                <div class="request-icon-small">
                  <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="request-title-text">
                  <h3>Certificate of Indigency</h3>
                  <p>Request ID: #BR-2025-001156</p>
                </div>
              </div>
              <span class="status-badge-large ready">Ready for Pickup</span>
            </div>
            <div class="request-details">
              <div class="detail-item">
                <span class="detail-label">Submitted Date</span>
                <span class="detail-value">Sept 20, 2025</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Approved Date</span>
                <span class="detail-value">Sept 27, 2025</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Purpose</span>
                <span class="detail-value">Medical Assistance</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Fee</span>
                <span class="detail-value">Free</span>
              </div>
            </div>
            <div class="request-actions">
              <button class="action-btn-small primary">
                <i class="fas fa-download"></i> Download Copy
              </button>
              <button class="action-btn-small secondary">
                <i class="fas fa-map-marker-alt"></i> Pickup Location
              </button>
            </div>
          </div>

          <!-- Request Card - Approved (Old) -->
          <div class="request-card approved">
            <div class="request-header">
              <div class="request-title">
                <div class="request-icon-small">
                  <i class="fas fa-award"></i>
                </div>
                <div class="request-title-text">
                  <h3>Good Moral Character</h3>
                  <p>Request ID: #BR-2025-001089</p>
                </div>
              </div>
              <span class="status-badge-large approved">Completed</span>
            </div>
            <div class="request-details">
              <div class="detail-item">
                <span class="detail-label">Submitted Date</span>
                <span class="detail-value">Sept 10, 2025</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Released Date</span>
                <span class="detail-value">Sept 18, 2025</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Purpose</span>
                <span class="detail-value">Job Application</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Fee</span>
                <span class="detail-value">₱50.00</span>
              </div>
            </div>
            <div class="request-actions">
              <button class="action-btn-small primary">
                <i class="fas fa-download">
                </i> Download Copy
              </button>
              <button class="action-btn-small secondary">
                <i class="fas fa-eye"></i> View Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include '../components/cdn_scripts.php'; ?>
  <?php include '../components/footer.php'; ?>

  <script>
    function openTab(evt, tabName) {
      // Hide all tab contents
      const tabContents = document.getElementsByClassName("tab-content");
      for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove("active");
      }

      // Remove active class from all tab buttons
      const tabBtns = document.getElementsByClassName("tab-btn");
      for (let i = 0; i < tabBtns.length; i++) {
        tabBtns[i].classList.remove("active");
      }

      // Show current tab and add active class to button
      document.getElementById(tabName).classList.add("active");
      evt.currentTarget.classList.add("active");
    }

    function filterRequests(status) {
      // Update filter buttons
      const filterBtns = document.querySelectorAll('.filter-btn');
      filterBtns.forEach(btn => btn.classList.remove('active'));
      event.currentTarget.classList.add('active');

      // Filter logic would go here
      console.log('Filtering by:', status);
    }

    function applyCertificate(type) {
      console.log('Applying for certificate:', type);
      // Application logic would go here
    }

    function applyPermit(type) {
      console.log('Applying for permit:', type);
      // Permit application logic would go here
    }
  </script>
</body>

</html>