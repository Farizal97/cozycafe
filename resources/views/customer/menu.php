<?php
ob_start();
?>
<main class="container page-section">
    <div class="page-heading">
        <div>
            <span class="section-kicker">MENU COZYCAFE</span>
            <h1>Temukan favorit Anda</h1>
            <p>Cari menu dan periksa ketersediaan sebelum menambahkan ke keranjang.</p>
        </div>
    </div>

    <form class="catalog-filter" method="get" action="<?= e($url('menu')) ?>">
        <label class="visually-hidden" for="menuSearch">Cari nama menu</label>
        <input class="form-control" id="menuSearch" name="q" type="search" maxlength="100" placeholder="Cari nama menu" value="<?= e($search) ?>">
        <label class="visually-hidden" for="categoryFilter">Filter kategori</label>
        <select class="form-select" id="categoryFilter" name="category">
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>"<?= (int) $selectedCategory === (int) $category['id'] ? ' selected' : '' ?>>
                    <?= e($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-cozy-primary" type="submit">Cari Menu</button>
        <?php if ($search !== '' || $selectedCategory !== null): ?>
            <a class="btn btn-cozy-outline" href="<?= e($url('menu')) ?>">Reset</a>
        <?php endif; ?>
    </form>

    <p class="catalog-result-count"><?= count($products) ?> menu ditemukan</p>
    <?php require $root . '/resources/views/components/product_cards.php'; ?>
</main>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/customer.php';
