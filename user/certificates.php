<?php
session_start();
require_once("../conn/conn.php");

if (!isset($_SESSION["user_id"])) {
  header("Location: ../index.php?auth=error");
  exit();
}

$user_id = $_SESSION["user_id"];

// Fetch all active certificates
$certificates_query = "SELECT * FROM document_types WHERE type = 'certificate' AND is_active = TRUE ORDER BY name ASC";
$certificates_result = $conn->query($certificates_query);

// Fetch all active permits
$permits_query = "SELECT * FROM document_types WHERE type = 'permit' AND is_active = TRUE ORDER BY name ASC";
$permits_result = $conn->query($permits_query);

// Fetch user's document requests with document type details
$requests_query = "SELECT dr.*, dt.name as document_name, dt.icon, dt.type as document_type, dt.fee
                   FROM document_requests dr
                   INNER JOIN document_types dt ON dr.document_type_id = dt.id
                   WHERE dr.user_id = ?
                   ORDER BY dr.submitted_date DESC";
$stmt = $conn->prepare($requests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests_result = $stmt->get_result();
$requests = $requests_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>

<html lang="en">

<head>
  <?php include '../components/header_links.php'; ?>
  <?php include '../components/user_side_header.php'; ?>
</head>
<style>
  /* View Details Modal - Formal Professional Styling */
  .swal2-popup.swal-view-simple {
    width: 90vw !important;
    max-width: 900px !important;
    padding: 0 !important;
    border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
  }

  .swal2-popup.swal-view-simple .swal2-title {
    font-size: 24px !important;
    font-weight: 700 !important;
    color: #1f2937 !important;
    padding: 25px 30px 20px !important;
    margin: 0 !important;
    border-bottom: 2px solid #e5e7eb !important;
    background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%) !important;
  }

  .swal2-popup.swal-view-simple .swal2-html-container {
    margin: 0 !important;
    padding: 0 !important;
    text-align: left !important;
    max-height: 70vh !important;
    overflow-y: auto !important;
  }

  /* Scrollbar styling for modal content */
  .swal2-popup.swal-view-simple .swal2-html-container::-webkit-scrollbar {
    width: 8px;
  }

  .swal2-popup.swal-view-simple .swal2-html-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
  }

  .swal2-popup.swal-view-simple .swal2-html-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
  }

  .swal2-popup.swal-view-simple .swal2-html-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  /* Main container for details */
  .resident-details-container-simple {
    padding: 0;
    background: #ffffff;
  }

  /* Profile header section with image and basic info */
  .profile-header-simple {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 30px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 2px solid #e5e7eb;
  }

  .resident-profile-image-simple {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #ffffff;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    flex-shrink: 0;
  }

  .profile-info-simple {
    flex: 1;
    min-width: 0;
  }

  .resident-name-simple {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 6px;
    line-height: 1.3;
  }

  .resident-email-simple {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 8px;
    font-weight: 500;
  }

  /* Section headers */
  .section-header-simple {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    padding: 25px 30px 15px;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e5e7eb;
    background: #fafbfc;
  }

  /* Grid layout for information items */
  .resident-info-grid-simple {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    padding: 25px 30px;
    background: #ffffff;
    border-bottom: 1px solid #f3f4f6;
  }

  .info-item-simple {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .info-item-simple.full-width {
    grid-column: 1 / -1;
  }

  .info-label-simple {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .info-value-simple {
    font-size: 15px;
    font-weight: 500;
    color: #1f2937;
    word-break: break-word;
    line-height: 1.5;
  }

  .info-value-simple i {
    margin-right: 6px;
    color: #667eea;
  }

  /* Photo container for reports */
  .photo-container-simple {
    padding: 20px 30px;
    background: #fafbfc;
    border-bottom: 1px solid #f3f4f6;
  }

  .photo-container-simple img {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }

  /* Close button styling */
  .swal2-popup.swal-view-simple .swal2-actions {
    padding: 20px 30px 25px !important;
    margin: 0 !important;
    border-top: 2px solid #e5e7eb !important;
    background: #fafbfc !important;
  }

  .swal2-popup.swal-view-simple .swal2-confirm {
    padding: 12px 32px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25) !important;
    transition: all 0.3s ease !important;
  }

  .swal2-popup.swal-view-simple .swal2-confirm:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.35) !important;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .swal2-popup.swal-view-simple {
      width: 95vw !important;
      max-width: 95vw !important;
    }

    .profile-header-simple {
      flex-direction: column;
      text-align: center;
      padding: 25px 20px;
    }

    .resident-profile-image-simple {
      width: 90px;
      height: 90px;
    }

    .resident-name-simple {
      font-size: 20px;
    }

    .section-header-simple {
      font-size: 14px;
      padding: 20px 20px 12px;
    }

    .resident-info-grid-simple {
      grid-template-columns: 1fr;
      gap: 18px;
      padding: 20px;
    }

    .info-item-simple.full-width {
      grid-column: 1;
    }

    .swal2-popup.swal-view-simple .swal2-title {
      font-size: 20px !important;
      padding: 20px !important;
    }
  }
</style>

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
          <?php if ($certificates_result->num_rows > 0): ?>
            <?php while ($cert = $certificates_result->fetch_assoc()): ?>
              <div class="certificate-card"
                onclick="applyCertificate(<?php echo $cert['id']; ?>, '<?php echo htmlspecialchars($cert['name']); ?>')">
                <div class="certificate-icon">
                  <i class="fas <?php echo htmlspecialchars($cert['icon']); ?>"></i>
                </div>
                <h3><?php echo htmlspecialchars($cert['name']); ?></h3>
                <p><?php echo htmlspecialchars($cert['description']); ?></p>
                <div class="certificate-meta">
                  <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($cert['processing_days']); ?></span>
                  <span><i class="fas fa-peso-sign"></i>
                    <?php echo $cert['fee'] == 0 ? 'Free' : '₱' . number_format($cert['fee'], 2); ?>
                  </span>
                </div>
                <button class="apply-btn">Apply Now</button>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="no-data-message">
              <i class="fas fa-inbox"></i>
              <p>No certificates available at the moment.</p>
            </div>
          <?php endif; ?>
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
          <?php if ($permits_result->num_rows > 0): ?>
            <?php while ($permit = $permits_result->fetch_assoc()): ?>
              <div class="certificate-card"
                onclick="applyPermit(<?php echo $permit['id']; ?>, '<?php echo htmlspecialchars($permit['name']); ?>')">
                <div class="certificate-icon">
                  <i class="fas <?php echo htmlspecialchars($permit['icon']); ?>"></i>
                </div>
                <h3><?php echo htmlspecialchars($permit['name']); ?></h3>
                <p><?php echo htmlspecialchars($permit['description']); ?></p>
                <div class="certificate-meta">
                  <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($permit['processing_days']); ?></span>
                  <span><i class="fas fa-peso-sign"></i> ₱<?php echo number_format($permit['fee'], 2); ?>+</span>
                </div>
                <button class="apply-btn">Apply Now</button>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="no-data-message">
              <i class="fas fa-inbox"></i>
              <p>No permits available at the moment.</p>
            </div>
          <?php endif; ?>
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
          <button class="filter-btn" onclick="filterRequests('completed')">Completed</button>
          <button class="filter-btn" onclick="filterRequests('rejected')">Rejected</button>
          <button class="filter-btn" onclick="filterRequests('cancelled')">Cancelled</button>
        </div>

        <div class="requests-list">
          <?php if (count($requests) > 0): ?>
            <?php foreach ($requests as $request): ?>
              <div class="request-card <?php echo $request['status']; ?>" data-status="<?php echo $request['status']; ?>">
                <div class="request-header">
                  <div class="request-title">
                    <div class="request-icon-small">
                      <i class="fas <?php echo htmlspecialchars($request['icon']); ?>"></i>
                    </div>
                    <div class="request-title-text">
                      <h3><?php echo htmlspecialchars($request['document_name']); ?></h3>
                      <p>Request ID: <?php echo htmlspecialchars($request['request_id']); ?></p>
                    </div>
                  </div>
                  <span class="status-badge-large <?php echo $request['status']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                  </span>
                </div>
                <div class="request-details">
                  <div class="detail-item">
                    <span class="detail-label">Submitted Date</span>
                    <span class="detail-value">
                      <?php echo date('M d, Y', strtotime($request['submitted_date'])); ?>
                    </span>
                  </div>

                  <?php if ($request['approved_date']): ?>
                    <div class="detail-item">
                      <span class="detail-label">Approved Date</span>
                      <span class="detail-value">
                        <?php echo date('M d, Y', strtotime($request['approved_date'])); ?>
                      </span>
                    </div>
                  <?php endif; ?>

                  <?php if ($request['released_date']): ?>
                    <div class="detail-item">
                      <span class="detail-label">Released Date</span>
                      <span class="detail-value">
                        <?php echo date('M d, Y', strtotime($request['released_date'])); ?>
                      </span>
                    </div>
                  <?php endif; ?>

                  <div class="detail-item">
                    <span class="detail-label">Purpose</span>
                    <span class="detail-value"><?php echo htmlspecialchars($request['purpose']); ?></span>
                  </div>

                  <div class="detail-item">
                    <span class="detail-label">Fee</span>
                    <span class="detail-value">
                      <?php echo $request['fee'] == 0 ? 'Free' : '₱' . number_format($request['fee'], 2); ?>
                    </span>
                  </div>

                  <?php if ($request['expected_date'] && in_array($request['status'], ['pending', 'processing'])): ?>
                    <div class="detail-item">
                      <span class="detail-label">Expected Date</span>
                      <span class="detail-value">
                        <?php echo date('M d, Y', strtotime($request['expected_date'])); ?>
                      </span>
                    </div>
                  <?php endif; ?>

                  <?php if ($request['status'] == 'rejected' && $request['rejection_reason']): ?>
                    <div class="detail-item" style="grid-column: 1 / -1;">
                      <span class="detail-label">Rejection Reason</span>
                      <span class="detail-value text-danger">
                        <?php echo htmlspecialchars($request['rejection_reason']); ?>
                      </span>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="request-actions">
                  <button class="action-btn-small secondary" onclick="viewRequestDetails(<?php echo $request['id']; ?>)">
                    <i class="fas fa-eye"></i> View Details
                  </button>

                  <?php if ($request['status'] == 'approved' && $request['fee'] > 0 && $request['payment_status'] == 'unpaid'): ?>
                    <button class="action-btn-small primary"
                      onclick="showPaymentModal(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['document_name']); ?>', <?php echo $request['fee']; ?>)">
                      <i class="fas fa-credit-card"></i> Pay Now
                    </button>
                  <?php endif; ?>

                  <?php if ($request['status'] == 'approved' && $request['payment_status'] == 'paid'): ?>
                    <span class="status-badge-large"
                      style="background: #28a745; color: white; padding: 8px 16px; font-size: 13px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                      <i class="fas fa-check-circle"></i> Payment Submitted
                    </span>
                  <?php endif; ?>

                  <?php if (in_array($request['status'], ['ready', 'completed'])): ?>
                    <button class="action-btn-small primary" onclick="downloadDocument(<?php echo $request['id']; ?>)">
                      <i class="fas fa-download"></i> Download Copy
                    </button>
                  <?php endif; ?>

                  <?php if ($request['status'] == 'ready'): ?>
                    <button class="action-btn-small secondary" onclick="showPickupLocation()">
                      <i class="fas fa-map-marker-alt"></i> Pickup Location
                    </button>
                  <?php endif; ?>

                  <?php if ($request['status'] == 'pending'): ?>
                    <button class="action-btn-small danger" onclick="cancelRequest(<?php echo $request['id']; ?>)">
                      <i class="fas fa-times"></i> Cancel Request
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="no-data-message" id="empty-all">
              <i class="fas fa-inbox"></i>
              <h3>No Requests Found</h3>
              <p>You haven't submitted any certificate or permit requests yet.</p>
              <button class="apply-btn"
                onclick="openTab(event, 'certificates'); document.querySelector('.tab-btn').click();">
                Apply for Certificate
              </button>
            </div>
          <?php endif; ?>

          <!-- Empty state messages for each filter -->
          <div class="no-data-message filter-empty" id="empty-pending" style="display: none;">
            <i class="fas fa-clock"></i>
            <h3>No Pending Requests</h3>
            <p>You don't have any pending requests at the moment.</p>
          </div>

          <div class="no-data-message filter-empty" id="empty-processing" style="display: none;">
            <i class="fas fa-spinner"></i>
            <h3>No Processing Requests</h3>
            <p>You don't have any requests being processed at the moment.</p>
          </div>

          <div class="no-data-message filter-empty" id="empty-approved" style="display: none;">
            <i class="fas fa-check-circle"></i>
            <h3>No Approved Requests</h3>
            <p>You don't have any approved requests at the moment.</p>
          </div>

          <div class="no-data-message filter-empty" id="empty-ready" style="display: none;">
            <i class="fas fa-box-open"></i>
            <h3>No Requests Ready for Pickup</h3>
            <p>You don't have any documents ready for pickup at the moment.</p>
          </div>

          <div class="no-data-message filter-empty" id="empty-completed" style="display: none;">
            <i class="fas fa-check-double"></i>
            <h3>No Completed Requests</h3>
            <p>You don't have any completed requests yet.</p>
          </div>

          <div class="no-data-message filter-empty" id="empty-rejected" style="display: none;">
            <i class="fas fa-times-circle"></i>
            <h3>No Rejected Requests</h3>
            <p>You don't have any rejected requests.</p>
          </div>

          <div class="no-data-message filter-empty" id="empty-cancelled" style="display: none;">
            <i class="fas fa-ban"></i>
            <h3>No Cancelled Requests</h3>
            <p>You don't have any cancelled requests.</p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include '../components/cdn_scripts.php'; ?>
  <?php include '../components/footer.php'; ?>

  <script>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'request_submitted'): ?>
      document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
          icon: 'success',
          title: 'Request Submitted!',
          text: 'Your document request has been successfully submitted.',
          confirmButtonText: 'OK',
          confirmButtonColor: '#667eea'
        }).then(() => {
          if (window.history.replaceState) {
            const url = window.location.origin + window.location.pathname;
            window.history.replaceState(null, '', url);
          }
        });
      });
    <?php endif; ?>

    function openTab(evt, tabName) {
      const tabContents = document.getElementsByClassName("tab-content");
      for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove("active");
      }

      const tabBtns = document.getElementsByClassName("tab-btn");
      for (let i = 0; i < tabBtns.length; i++) {
        tabBtns[i].classList.remove("active");
      }

      document.getElementById(tabName).classList.add("active");
      evt.currentTarget.classList.add("active");
    }

    function filterRequests(status) {
      const filterBtns = document.querySelectorAll('.filter-btn');
      filterBtns.forEach(btn => btn.classList.remove('active'));
      event.currentTarget.classList.add('active');

      const requestCards = document.querySelectorAll('.request-card');
      const emptyMessages = document.querySelectorAll('.filter-empty');

      // Hide all empty messages first
      emptyMessages.forEach(msg => msg.style.display = 'none');

      let visibleCount = 0;

      requestCards.forEach(card => {
        if (status === 'all') {
          card.style.display = 'block';
          visibleCount++;
        } else {
          const cardStatus = card.getAttribute('data-status');
          if (cardStatus === status) {
            card.style.display = 'block';
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        }
      });

      // Show appropriate empty message if no cards are visible
      if (visibleCount === 0 && status !== 'all') {
        const emptyMessage = document.getElementById(`empty-${status}`);
        if (emptyMessage) {
          emptyMessage.style.display = 'block';
        }
      }
    }

    function applyCertificate(id, name) {
      // Show loading
      Swal.fire({
        title: 'Checking...',
        text: 'Please wait while we verify your request limit.',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Check daily limit first
      fetch('./endpoints/check_daily_limit.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && !data.limit_reached) {
            // User can proceed - show remaining requests info
            const remainingText = data.remaining === 1
              ? 'This is your last request for today.'
              : `You have ${data.remaining} request${data.remaining > 1 ? 's' : ''} remaining today.`;

            Swal.fire({
              title: 'Proceed with Application?',
              html: `
            <p>You are about to apply for <strong>${name}</strong>.</p>
            <p class="text-muted" style="font-size: 14px; margin-top: 10px;">
              <i class="fas fa-info-circle"></i> ${remainingText}
            </p>
          `,
              icon: 'info',
              showCancelButton: true,
              confirmButtonColor: '#667eea',
              cancelButtonColor: '#6c757d',
              confirmButtonText: 'Continue',
              cancelButtonText: 'Cancel'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = `apply_document.php?type=certificate&id=${id}`;
              }
            });
          } else if (data.limit_reached) {
            // Daily limit reached
            Swal.fire({
              icon: 'warning',
              title: 'Daily Limit Reached',
              html: `
            <p>You've reached your daily limit of <strong>${data.daily_limit}</strong> document requests.</p>
            <p class="text-muted" style="margin-top: 10px;">
              <i class="fas fa-clock"></i> You can submit more requests tomorrow.
            </p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px;">
              <p style="margin: 0; font-size: 14px; color: #6c757d;">
                <strong>Today's Requests:</strong> ${data.current_count} / ${data.daily_limit}
              </p>
            </div>
          `,
              confirmButtonText: 'Understood',
              confirmButtonColor: '#667eea',
              footer: '<span style="color: #6c757d; font-size: 12px;">This limit helps us process requests efficiently.</span>'
            });
          } else {
            // Error occurred
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Unable to verify request limit. Please try again.',
              confirmButtonColor: '#667eea'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Unable to connect to server. Please try again.',
            confirmButtonColor: '#667eea'
          });
        });
    }

    function applyPermit(id, name) {
      // Show loading
      Swal.fire({
        title: 'Checking...',
        text: 'Please wait while we verify your request limit.',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // Check daily limit first
      fetch('./endpoints/check_daily_limit.php')
        .then(response => response.json())
        .then(data => {
          if (data.success && !data.limit_reached) {
            // User can proceed - show remaining requests info
            const remainingText = data.remaining === 1
              ? 'This is your last request for today.'
              : `You have ${data.remaining} request${data.remaining > 1 ? 's' : ''} remaining today.`;

            Swal.fire({
              title: 'Proceed with Application?',
              html: `
            <p>You are about to apply for <strong>${name}</strong>.</p>
            <p class="text-muted" style="font-size: 14px; margin-top: 10px;">
              <i class="fas fa-info-circle"></i> ${remainingText}
            </p>
          `,
              icon: 'info',
              showCancelButton: true,
              confirmButtonColor: '#667eea',
              cancelButtonColor: '#6c757d',
              confirmButtonText: 'Continue',
              cancelButtonText: 'Cancel'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = `apply_document.php?type=permit&id=${id}`;
              }
            });
          } else if (data.limit_reached) {
            // Daily limit reached
            Swal.fire({
              icon: 'warning',
              title: 'Daily Limit Reached',
              html: `
            <p>You've reached your daily limit of <strong>${data.daily_limit}</strong> document requests.</p>
            <p class="text-muted" style="margin-top: 10px;">
              <i class="fas fa-clock"></i> You can submit more requests tomorrow.
            </p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px;">
              <p style="margin: 0; font-size: 14px; color: #6c757d;">
                <strong>Today's Requests:</strong> ${data.current_count} / ${data.daily_limit}
              </p>
            </div>
          `,
              confirmButtonText: 'Understood',
              confirmButtonColor: '#667eea',
              footer: '<span style="color: #6c757d; font-size: 12px;">This limit helps us process requests efficiently.</span>'
            });
          } else {
            // Error occurred
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Unable to verify request limit. Please try again.',
              confirmButtonColor: '#667eea'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Unable to connect to server. Please try again.',
            confirmButtonColor: '#667eea'
          });
        });
    }

    function viewRequestDetails(requestId) {
      Swal.fire({
        title: 'Loading...',
        text: 'Fetching request details.',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch(`./endpoints/get_request_details.php?id=${requestId}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const request = data.request;

            // Format dates
            const submittedDate = request.submitted_date ? new Date(request.submitted_date).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            }) : 'N/A';

            const approvedDate = request.approved_date ? new Date(request.approved_date).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            }) : 'N/A';

            const releasedDate = request.released_date ? new Date(request.released_date).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            }) : 'N/A';

            const expectedDate = request.expected_date ? new Date(request.expected_date).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            }) : 'N/A';

            // Get user profile image path
            const profileImage = request.user_image ?
              `../assets/images/user/${request.user_image}` :
              '../assets/images/user.png';

            // Format status with color
            const getStatusStyle = (status) => {
              switch (status) {
                case 'completed':
                  return 'color: #d1fae5; background: #6c757d;';
                case 'approved':
                  return 'color: #dbeafe; background: #28a745;';
                case 'ready':
                  return 'color: #e0e7ff; background: #20c997;';
                case 'processing':
                  return 'color: #fef3c7; background: #17a2b8;';
                case 'pending':
                  return 'color: #fef3c7; background: #ffc107;';
                case 'rejected':
                  return 'color: #fee2e2; background: #dc2626;';
                case 'cancelled':
                  return 'color: #f3f4f6; background: #dc3545;';
                default:
                  return 'color: #fef3c7; background: #d97706;';
              }
            };

            // Document type badge
            const typeStyle = request.document_type === 'certificate' ?
              'background: #ddd6fe; color: #7c3aed;' :
              'background: #fef3c7; color: #d97706;';

            Swal.fire({
              title: 'Document Request Details',
              html: `
                    <div class="resident-details-container-simple">
                        <!-- Requester Header with Profile Image -->
                        <div class="profile-header-simple">
                            <img src="${profileImage}" alt="Profile" class="resident-profile-image-simple">
                            <div class="profile-info-simple">
                                <div class="resident-name-simple">
                                    ${request.first_name} ${request.middle_name || ''} ${request.last_name}
                                </div>
                                <div class="resident-email-simple">${request.email || 'N/A'}</div>
                                <div style="display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 8px; ${getStatusStyle(request.status)}">
                                    ${request.status.toUpperCase().replace('_', ' ')}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Request Information Section -->
                        <div class="section-header-simple">Request Information</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">Request ID</div>
                                <div class="info-value-simple">${request.request_id}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Document Type</div>
                                <div class="info-value-simple">
                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; ${typeStyle}">
                                        ${request.document_type.toUpperCase()}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Document Name</div>
                                <div class="info-value-simple">
                                    <i class="fas ${request.icon}"></i> ${request.document_name}
                                </div>
                            </div>
                            
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Purpose</div>
                                <div class="info-value-simple">${request.purpose || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Fee</div>
                                <div class="info-value-simple">
                                    ${request.fee == 0 ? '<span style="color: #059669; font-weight: 600;">Free</span>' : '₱' + parseFloat(request.fee).toFixed(2)}
                                </div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Processing Time</div>
                                <div class="info-value-simple">${request.processing_days || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <!-- Requester Details Section -->
                        <div class="section-header-simple">Requester Information</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">First Name</div>
                                <div class="info-value-simple">${request.first_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Middle Name</div>
                                <div class="info-value-simple">${request.middle_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Last Name</div>
                                <div class="info-value-simple">${request.last_name || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Email Address</div>
                                <div class="info-value-simple">${request.email || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Contact Number</div>
                                <div class="info-value-simple">${request.contact_number || 'N/A'}</div>
                            </div>
                            
                            <div class="info-item-simple">
                                <div class="info-label-simple">Address</div>
                                <div class="info-value-simple">${request.address || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <!-- Timeline Section -->
                        <div class="section-header-simple">Request Timeline</div>
                        <div class="resident-info-grid-simple">
                            <div class="info-item-simple">
                                <div class="info-label-simple">Date Submitted</div>
                                <div class="info-value-simple">${submittedDate}</div>
                            </div>
                            
                            ${request.expected_date && ['pending', 'processing'].includes(request.status) ? `
                            <div class="info-item-simple">
                                <div class="info-label-simple">Expected Completion</div>
                                <div class="info-value-simple">${expectedDate}</div>
                            </div>
                            ` : ''}
                            
                            ${request.approved_date ? `
                            <div class="info-item-simple">
                                <div class="info-label-simple">Date Approved</div>
                                <div class="info-value-simple">${approvedDate}</div>
                            </div>
                            ` : ''}
                            
                            ${request.released_date ? `
                            <div class="info-item-simple">
                                <div class="info-label-simple">Date Released</div>
                                <div class="info-value-simple">${releasedDate}</div>
                            </div>
                            ` : ''}
                            
                            ${request.rejection_reason && request.status === 'rejected' ? `
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Rejection Reason</div>
                                <div class="info-value-simple" style="color: #dc2626; font-weight: 500;">
                                    ${request.rejection_reason}
                                </div>
                            </div>
                            ` : ''}
                            
                            ${request.cancellation_reason && request.status === 'cancelled' ? `
                            <div class="info-item-simple full-width">
                                <div class="info-label-simple">Cancellation Reason</div>
                                <div class="info-value-simple" style="color: #6b7280;">
                                    ${request.cancellation_reason}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `,
              customClass: {
                popup: 'swal-view-simple'
              },
              confirmButtonText: 'Close',
              confirmButtonColor: '#667eea',
              width: '700px'
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Unable to fetch request details.',
              confirmButtonText: 'OK',
              confirmButtonColor: '#667eea'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Unable to connect to server.',
            confirmButtonText: 'OK',
            confirmButtonColor: '#667eea'
          });
        });
    }

    function downloadDocument(requestId) {
      window.location.href = `./endpoints/download_document.php?id=${requestId}`;
    }

    function showPickupLocation() {
      Swal.fire({
        icon: 'info',
        title: 'Pickup Location',
        html: '<strong>Barangay Hall</strong><br>Monday-Friday: 8:00 AM - 5:00 PM<br><br>Please bring a valid ID for verification.',
        confirmButtonText: 'Got it',
        confirmButtonColor: '#667eea'
      });
    }

    function cancelRequest(requestId) {
      Swal.fire({
        title: 'Cancel Request?',
        text: "Are you sure you want to cancel this request? This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, cancel it',
        cancelButtonText: 'No, keep it'
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            title: 'Cancelling...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch('./endpoints/cancel_request.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ request_id: requestId })
          })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: 'Cancelled!',
                  text: 'Your request has been cancelled successfully.',
                  confirmButtonColor: '#667eea'
                }).then(() => {
                  location.reload();
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: data.message || 'Failed to cancel request',
                  confirmButtonColor: '#667eea'
                });
              }
            })
            .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while cancelling the request',
                confirmButtonColor: '#667eea'
              });
            });
        }
      });
    }

    function showPaymentModal(requestId, documentName, fee) {
      Swal.fire({
        title: 'Loading Payment Details...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch(`./endpoints/get_gcash_qr.php?request_id=${requestId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              title: 'Payment for ' + documentName,
              html: `
            <div style="text-align: center; padding: 20px;">
              <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <p style="font-size: 16px; color: #6c757d; margin-bottom: 10px;">Amount to Pay</p>
                <p style="font-size: 32px; font-weight: bold; color: #28a745; margin: 0;">₱${parseFloat(fee).toFixed(2)}</p>
                <p style="font-size: 12px; color: #6c757d; margin-top: 10px;">Request ID: ${data.request_id}</p>
              </div>
              
              <div style="margin: 20px 0;">
                <p style="font-weight: 600; margin-bottom: 15px; color: #495057;">Scan GCash QR Code to Pay</p>
                <img src="../assets/images/gcash/${data.gcash_qr}" alt="GCash QR Code" 
                     style="max-width: 300px; width: 100%; border: 3px solid #e9ecef; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
              </div>
              
              <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <p style="color: #856404; margin: 0; font-size: 14px;">
                  <i class="fas fa-exclamation-triangle"></i> 
                  After payment, please upload your receipt/screenshot for verification.
                </p>
              </div>
            </div>
          `,
              showCancelButton: true,
              confirmButtonText: '<i class="fas fa-upload"></i> Upload Receipt',
              cancelButtonText: 'Cancel',
              confirmButtonColor: '#28a745',
              cancelButtonColor: '#6c757d',
              width: '600px'
            }).then((result) => {
              if (result.isConfirmed) {
                showUploadReceiptModal(requestId, documentName, fee);
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Unable to load payment details',
              confirmButtonColor: '#667eea'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: 'Unable to connect to server',
            confirmButtonColor: '#667eea'
          });
        });
    }

    function showUploadReceiptModal(requestId, documentName, fee) {
      Swal.fire({
        title: 'Upload Payment Receipt',
        html: `
      <div style="text-align: left; padding: 20px;">
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
          <p style="margin: 5px 0;"><strong>Document:</strong> ${documentName}</p>
          <p style="margin: 5px 0;"><strong>Amount Paid:</strong> ₱${parseFloat(fee).toFixed(2)}</p>
        </div>
        
        <div style="margin: 20px 0;">
          <label for="receipt-file" style="display: block; margin-bottom: 10px; font-weight: 600; color: #495057;">
            <i class="fas fa-receipt"></i> Select Receipt/Screenshot
          </label>
          <input type="file" id="receipt-file" accept="image/*" 
                 style="width: 100%; padding: 10px; border: 2px dashed #dee2e6; border-radius: 8px; cursor: pointer;"
                 onchange="previewReceipt(this)">
          <p style="font-size: 12px; color: #6c757d; margin-top: 8px;">
            Supported formats: JPG, PNG, GIF (Max 5MB)
          </p>
        </div>
        
        <div id="receipt-preview" style="margin: 20px 0; display: none;">
          <p style="font-weight: 600; margin-bottom: 10px; color: #495057;">Preview:</p>
          <img id="preview-image" src="" alt="Receipt Preview" 
               style="max-width: 100%; border: 2px solid #dee2e6; border-radius: 8px;">
        </div>
        
        <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 8px; margin-top: 20px;">
          <p style="color: #0c5460; margin: 0; font-size: 13px;">
            <i class="fas fa-info-circle"></i> 
            Your payment will be verified by the administrator. You will be notified once verified.
          </p>
        </div>
      </div>
    `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Submit Receipt',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        width: '600px',
        preConfirm: () => {
          const fileInput = document.getElementById('receipt-file');
          if (!fileInput.files || fileInput.files.length === 0) {
            Swal.showValidationMessage('Please select a receipt file');
            return false;
          }
          return fileInput.files[0];
        }
      }).then((result) => {
        if (result.isConfirmed && result.value) {
          uploadReceipt(requestId, result.value);
        }
      });
    }

    function previewReceipt(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          document.getElementById('preview-image').src = e.target.result;
          document.getElementById('receipt-preview').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function uploadReceipt(requestId, file) {
      Swal.fire({
        title: 'Uploading Receipt...',
        text: 'Please wait while we process your payment receipt',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const formData = new FormData();
      formData.append('request_id', requestId);
      formData.append('receipt', file);

      fetch('./endpoints/upload_payment_receipt.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Receipt Uploaded!',
              html: `
            <p>${data.message}</p>
            <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; margin-top: 15px;">
              <p style="color: #0c5460; margin: 0; font-size: 14px;">
                <i class="fas fa-clock"></i> 
                Your payment is now pending verification. This usually takes 1-2 business days.
              </p>
            </div>
          `,
              confirmButtonColor: '#28a745'
            }).then(() => {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Upload Failed',
              text: data.message || 'Failed to upload receipt',
              confirmButtonColor: '#667eea'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while uploading the receipt',
            confirmButtonColor: '#667eea'
          });
        });
    }
  </script>

</body>

</html>