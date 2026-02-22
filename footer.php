                <?php
                if(is_admin_login())
                {
                ?>
                </main>
                <footer class="py-4 bg-white border-top mt-auto">
                    <div class="container-fluid px-4">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between small gap-2">
                            <div class="fw-semibold">LibraryOS Admin Console</div>
                            <div class="text-muted">&copy; <?php echo date('Y'); ?> LibraryOS</div>
                            <div class="d-flex gap-3">
                                <a href="#" class="text-decoration-none">Privacy</a>
                                <a href="#" class="text-decoration-none">Terms</a>
                                <a href="#" class="text-decoration-none">Support</a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
                <?php
                }
                else
                {
                ?>
                <footer class="pt-4 mt-4 border-top">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="fw-bold">LibraryOS</h6>
                            <p class="text-muted">Premium library experience powered by PHP &amp; MySQL.</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold">Explore</h6>
                            <ul class="list-unstyled">
                                <li><a class="text-decoration-none" href="search_book.php">Search</a></li>
                                <li><a class="text-decoration-none" href="issue_book_details.php">Issue History</a></li>
                                <li><a class="text-decoration-none" href="profile.php">Profile</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold">Connect</h6>
                            <ul class="list-unstyled">
                                <li><a class="text-decoration-none" href="#">Privacy</a></li>
                                <li><a class="text-decoration-none" href="#">Terms</a></li>
                                <li><a class="text-decoration-none" href="mailto:contact@example.com">contact@example.com</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="text-center text-muted mt-3">&copy; <?php echo date('Y'); ?> LibraryOS</div>
                </footer>
            </div>
        </main>
                <?php 
                }
                ?>

    	<script src="<?php echo base_url(); ?>asset/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="<?php echo base_url(); ?>asset/js/scripts.js"></script>
        <script src="<?php echo base_url(); ?>asset/js/simple-datatables@latest.js" crossorigin="anonymous"></script>
        <script src="<?php echo base_url(); ?>asset/js/datatables-simple-demo.js"></script>

    </body>

</html>