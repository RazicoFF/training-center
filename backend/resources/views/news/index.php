<?php
/** @var array $newsItems */
use App\Core\Csrf;
use App\Core\Lang;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4"><?= htmlspecialchars(Lang::t('nav_news')) ?></h1>
    <a href="/admin/news/create" class="btn btn-primary btn-sm"><?= htmlspecialchars(Lang::t('news_create')) ?></a>
</div>
<div class="row g-3">
    <?php foreach ($newsItems as $i => $n): ?>
        <div class="col-md-4">
            <div class="card tc-fade-in tc-fade-in-<?= min($i + 1, 4) ?> h-100">
                <?php if (!empty($n['image_url'])): ?>
                    <img src="<?= htmlspecialchars($n['image_url']) ?>" alt="" style="width:100%;height:140px;object-fit:cover;">
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="h6"><?= htmlspecialchars($n['title_uz']) ?></h2>
                    <p class="text-muted small mb-2"><?= htmlspecialchars($n['published_at']) ?></p>
                    <div class="d-flex gap-2">
                        <a href="/admin/news/<?= (int) $n['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><?= htmlspecialchars(Lang::t('teacher_edit')) ?></a>
                        <form method="post" action="/admin/news/<?= (int) $n['id'] ?>/delete">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(Lang::t('video_delete')) ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
