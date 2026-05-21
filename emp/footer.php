<footer class="footer pt-3  ">
        <div class="container-fluid">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-6 mb-lg-0 mb-4">
              <div class="copyright text-center text-sm text-muted text-lg-start">
                © <script>
                  document.write(new Date().getFullYear())
                </script>,
               Maison Technology All Rights Reserved.  
                <a href="<?= $org['website'] ?>" class="font-weight-bold" target="_blank"> </a>
               
              </div>
            </div>
            <div class="col-lg-6">
              <ul class="nav nav-footer justify-content-center justify-content-lg-end">
                <li class="nav-item">
                  <a href="https://www.maisontechnology.com" class="nav-link text-muted" target="_blank">Powered By : Myattendance</a>
                </li>
             
              
              </ul>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </main>
  <!--   Core JS Files   -->
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap.min.js"></script>
  <script src="assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="assets/js/plugins/chartjs.min.js"></script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>



  <!-- End Journey Modal -->
<div id="endJourneyModal" style="
    display:none;
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.6);
    z-index:9999;
    align-items:center;
    justify-content:center;
">
    <div style="
        background:#fff;
        padding:22px;
        border-radius:12px;
        width:90%;
        max-width:420px;
        text-align:center;
    ">
        <h5> End Journey</h5>
        <p style="margin-top:10px;">
            Are you sure you want to end your journey?<br>
            <small>This action cannot be undone.</small>
        </p>

        <div style="margin-top:20px; display:flex; gap:10px; justify-content:center;">
            <button class="btn btn-secondary btn-sm" onclick="closeEndJourneyModal()">
                Cancel
            </button>
            <button class="btn btn-sm"
                    style="background:#023e7f; color:#fff; border:none;"
                    onclick="confirmEndJourney()">
                Yes, End Journey
            </button>

        </div>
    </div>
</div>

</body>

</html>