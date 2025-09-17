<!-- Pagination -->
<nav aria-label="Page navigation">
    <ul class="pagination justify-content-center">
        <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" 
                   href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        <?php endif; ?>
        
        <?php 
        $paginationRange = getPaginationRange($currentPage, $totalPages);
        foreach ($paginationRange as $pageNum): 
        ?>
            <li class="page-item <?php echo $pageNum === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" 
                   href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pageNum])); ?>">
                    <?php echo $pageNum; ?>
                </a>
            </li>
        <?php endforeach; ?>
        
        <?php if ($currentPage < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" 
                   href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<div class="text-center text-muted mt-2">
    <?php echo $lang === 'ja' ? 'ページ' : 'Page'; ?> <?php echo $currentPage; ?> / <?php echo $totalPages; ?>
    (<?php echo $totalBuildings; ?> <?php echo $lang === 'ja' ? '件' : 'items'; ?>)
</div>

