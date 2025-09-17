<!-- Footer -->
<footer class="bg-light mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold">PocketNavi</h6>
                <p class="text-muted small">
                    <?php echo $lang === 'ja' ? 
                        '日本の建築物を検索・閲覧できるWebアプリケーション' : 
                        'A web application for searching and browsing Japanese buildings'; ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted small mb-0">
                    &copy; <?php echo date('Y'); ?> PocketNavi. 
                    <?php echo $lang === 'ja' ? 'All rights reserved.' : 'All rights reserved.'; ?>
                </p>
            </div>
        </div>
    </div>
</footer>

