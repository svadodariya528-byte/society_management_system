<?php
include 'r_layout.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <div class="pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-file-invoice-dollar me-2"></i>Maintenance Payments</h1>
        <p class="text-muted">View your monthly maintenance bills and pay online or mark as paid.</p>
    </div>

    <!-- Bill Card Example -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-calendar-alt me-2"></i>March 2025</h5>
            <span class="badge bg-danger">Pending</span>
        </div>
        <div class="card-body">
            <p><strong>Bill Amount:</strong> ₹5,000</p>
            <p><strong>Due Date:</strong> 31 March 2025</p>
            <p><strong>Description:</strong> Monthly maintenance charges</p>
            <div class="d-flex justify-content-end">
                <button class="btn btn-success me-2"><i class="fas fa-credit-card me-1"></i>Pay Online</button>
                <button class="btn btn-outline-secondary">Mark as Paid</button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-calendar-alt me-2"></i>February 2025</h5>
            <span class="badge bg-success">Paid</span>
        </div>
        <div class="card-body">
            <p><strong>Bill Amount:</strong> ₹4,800</p>
            <p><strong>Due Date:</strong> 28 February 2025</p>
            <p><strong>Description:</strong> Monthly maintenance charges</p>
            <div class="d-flex justify-content-end">
                <button class="btn btn-success me-2" disabled><i class="fas fa-credit-card me-1"></i>Pay Online</button>
                <button class="btn btn-outline-secondary" disabled>Mark as Paid</button>
            </div>
        </div>
    </div>

    <!-- No bills placeholder -->
    <!-- <div class="text-center text-muted my-5">No bills available at the moment.</div> -->

</main>
</div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(document).ready(function(){
    $('.btn-outline-secondary').on('click', function(){
        alert('Mark as Paid functionality is not implemented yet.');
    });

    $('.btn-success').on('click', function(){
        alert('Online payment functionality is not implemented yet.');
    });
});
</script>
</body>
</html>
