<?php
$isVoucher = $discountType === 'voucher';
$isEditing = is_array($discount);
$dateValue = static fn (mixed $value): string => $value ? date('Y-m-d\TH:i', strtotime((string) $value)) : '';
$action = $isVoucher
    ? ($isEditing ? 'admin/vouchers/update' : 'admin/vouchers/store')
    : ($isEditing ? 'admin/promotions/update' : 'admin/promotions/store');
$returnPath = 'admin/promotions';
ob_start();
?>
<section class="admin-form-card operation-form-card">
    <a class="back-link" href="<?= e($url($returnPath)) ?>">← Kembali ke voucher dan promosi</a>
    <span class="section-kicker"><?= $isVoucher ? 'KODE POTONGAN' : 'DISKON OTOMATIS' ?></span>
    <h2><?= $isEditing ? 'Ubah ' : 'Buat ' ?><?= $isVoucher ? 'voucher' : 'promosi' ?></h2>
    <p class="text-secondary"><?= $isVoucher ? 'Kode dibagikan ke pelanggan dan dimasukkan saat checkout.' : 'Promosi aktif diterapkan otomatis saat checkout jika syarat terpenuhi.' ?></p>
    <form method="post" action="<?= e($url($action)) ?>" class="admin-form">
        <?= csrfField() ?>
        <?php if ($isEditing): ?><input type="hidden" name="id" value="<?= (int) $discount['id'] ?>"><?php endif; ?>
        <?php if ($isVoucher): ?>
            <div><label class="form-label" for="discountCode">Kode voucher</label><input class="form-control text-uppercase" id="discountCode" name="code" maxlength="64" pattern="[A-Za-z0-9_-]{3,64}" value="<?= e($discount['code'] ?? '') ?>" required><div class="form-text">3–64 karakter: huruf, angka, tanda hubung, atau garis bawah.</div></div>
        <?php else: ?>
            <div><label class="form-label" for="promotionName">Nama promosi</label><input class="form-control" id="promotionName" name="name" maxlength="160" value="<?= e($discount['name'] ?? '') ?>" required></div>
        <?php endif; ?>
        <div>
            <label class="form-label" for="discountType">Jenis diskon</label>
            <select class="form-select" id="discountType" name="discount_type" required>
                <option value="percentage" <?= ($discount['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Persentase (%)</option>
                <option value="fixed" <?= ($discount['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Nominal tetap (Rp)</option>
            </select>
        </div>
        <div><label class="form-label" for="discountValue">Nilai diskon</label><input class="form-control" id="discountValue" name="discount_value" type="number" min="0.01" step="0.01" value="<?= e($discount['discount_value'] ?? '') ?>" required></div>
        <div><label class="form-label" for="minimumPurchase">Minimum pembelian (Rp)</label><input class="form-control" id="minimumPurchase" name="minimum_purchase" type="number" min="0" step="0.01" value="<?= e($discount['minimum_purchase'] ?? '0') ?>" required></div>
        <?php if ($isVoucher): ?>
            <div><label class="form-label" for="totalUsageLimit">Batas total penggunaan</label><input class="form-control" id="totalUsageLimit" name="total_usage_limit" type="number" min="1" value="<?= e($discount['total_usage_limit'] ?? '') ?>" placeholder="Kosong = tanpa batas"></div>
            <div><label class="form-label" for="perPhoneLimit">Batas per nomor telepon</label><input class="form-control" id="perPhoneLimit" name="per_phone_limit" type="number" min="1" value="<?= e($discount['per_phone_limit'] ?? '') ?>" placeholder="Kosong = tanpa batas"></div>
        <?php endif; ?>
        <div><label class="form-label" for="startDate">Mulai berlaku</label><input class="form-control" id="startDate" name="start_date" type="datetime-local" value="<?= e($dateValue($discount['start_date'] ?? null)) ?>" required></div>
        <div><label class="form-label" for="endDate">Berakhir</label><input class="form-control" id="endDate" name="end_date" type="datetime-local" value="<?= e($dateValue($discount['end_date'] ?? null)) ?>" required></div>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= !$isEditing || (int) $discount['is_active'] === 1 ? 'checked' : '' ?>><span>Aktifkan sekarang (tetap mengikuti periode berlaku)</span></label>
        <button class="btn btn-cozy-primary" type="submit">Simpan <?= $isVoucher ? 'voucher' : 'promosi' ?></button>
    </form>
</section>
<?php
$content = ob_get_clean();
require $root . '/resources/views/layouts/admin.php';
