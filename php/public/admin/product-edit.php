<?php

declare(strict_types=1);

require dirname(__DIR__) . '/_inc/bootstrap.php';
require_once dirname(__DIR__) . '/_inc/auth.php';

pithead_require_admin();
$pdo = pithead_pdo();

$id = (int) ($_GET['id'] ?? 0);
$errors = [];
$msg = '';

$cats = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pithead_csrf_validate((string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'Invalid CSRF.';
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $tagline = trim((string) ($_POST['tagline'] ?? ''));
        $short = trim((string) ($_POST['short_description'] ?? ''));
        $pricePounds = (float) str_replace(',', '.', (string) ($_POST['price'] ?? '0'));
        $priceCents = (int) round($pricePounds * 100);
        $weight = trim((string) ($_POST['weight_label'] ?? ''));
        $sku = trim((string) ($_POST['sku'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $brew = trim((string) ($_POST['brew_suggestions'] ?? ''));
        $specsRaw = trim((string) ($_POST['specs'] ?? ''));
        $specsJson = null;
        if ($specsRaw !== '') {
            try {
                $dec = json_decode($specsRaw, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($dec)) {
                    $errors[] = 'Specs must be a JSON object.';
                } else {
                    $specsJson = json_encode($dec, JSON_THROW_ON_ERROR);
                }
            } catch (JsonException $je) {
                $errors[] = 'Specs JSON invalid.';
            }
        }

        if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = 'Slug: lowercase letters, numbers, hyphens only.';
        }
        if ($name === '') {
            $errors[] = 'Name required.';
        }
        if ($categoryId <= 0) {
            $errors[] = 'Category required.';
        }
        if ($priceCents <= 0) {
            $errors[] = 'Price must be positive.';
        }

        if ($errors === []) {
            try {
                if ($id === 0) {
                    $st = $pdo->prepare(
                        'INSERT INTO products (category_id, slug, name, tagline, short_description, price_cents, weight_label, sku, is_active, is_featured, brew_suggestions, specs)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                    );
                    $st->execute([
                        $categoryId,
                        $slug,
                        $name,
                        $tagline,
                        $short,
                        $priceCents,
                        $weight,
                        $sku,
                        $isActive,
                        $isFeatured,
                        $brew,
                        $specsJson,
                    ]);
                    $id = (int) $pdo->lastInsertId();
                    $msg = 'Created.';
                } else {
                    $chk = $pdo->prepare('SELECT id FROM products WHERE id = ?');
                    $chk->execute([$id]);
                    if ($chk->fetch() === false) {
                        $errors[] = 'Product not found.';
                    } else {
                        $st = $pdo->prepare(
                            'UPDATE products SET category_id=?, slug=?, name=?, tagline=?, short_description=?, price_cents=?, weight_label=?, sku=?, is_active=?, is_featured=?, brew_suggestions=?, specs=?
                             WHERE id=?'
                        );
                        $st->execute([
                            $categoryId,
                            $slug,
                            $name,
                            $tagline,
                            $short,
                            $priceCents,
                            $weight,
                            $sku,
                            $isActive,
                            $isFeatured,
                            $brew,
                            $specsJson,
                            $id,
                        ]);
                        $msg = 'Saved.';
                    }
                }

                if ($errors === [] && $id > 0 && isset($_FILES['image']) && is_array($_FILES['image']['tmp_name'] ?? null)) {
                    $tmp = (string) $_FILES['image']['tmp_name'];
                    if (is_uploaded_file($tmp)) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->file($tmp) ?: '';
                        $extMap = [
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/webp' => 'webp',
                        ];
                        if (!isset($extMap[$mime])) {
                            $errors[] = 'Image must be JPEG, PNG, or WebP.';
                        } else {
                            $ext = $extMap[$mime];
                            $dir = dirname(__DIR__) . '/uploads/products';
                            if (!is_dir($dir)) {
                                mkdir($dir, 0755, true);
                            }
                            $basename = $id . '.' . $ext;
                            $dest = $dir . '/' . $basename;
                            if (!move_uploaded_file($tmp, $dest)) {
                                $errors[] = 'Upload failed.';
                            } else {
                                $webPath = '/uploads/products/' . $basename;
                                $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
                                $pdo->prepare(
                                    'INSERT INTO product_images (product_id, path, sort_order, is_primary) VALUES (?, ?, 0, 1)'
                                )->execute([$id, $webPath]);
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                if ((int) $e->errorInfo[1] === 1062) {
                    $errors[] = 'Duplicate slug.';
                } else {
                    $errors[] = 'Database error.';
                }
            }
        }
    }
}

$row = null;
if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch() ?: null;
}

$csrf = pithead_csrf_token();
$imgSt = $id > 0 ? $pdo->prepare('SELECT path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1') : null;
$primaryPath = '';
if ($imgSt) {
    $imgSt->execute([$id]);
    $im = $imgSt->fetch();
    if ($im !== false) {
        $primaryPath = (string) $im['path'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="/assets/app.css" />
  <title>Product — Admin</title>
</head>
<body class="min-h-screen bg-coal text-offwhite">
<?php require __DIR__ . '/_nav.php'; ?>
  <main class="mx-auto max-w-3xl px-4 py-10">
    <h1 class="text-2xl font-bold uppercase tracking-tight"><?= $id ? 'Edit product' : 'New product' ?></h1>
    <?php foreach ($errors as $e) : ?>
      <p class="mt-2 text-sm text-imperial"><?= pithead_h($e) ?></p>
    <?php endforeach; ?>
    <?php if ($msg !== '') : ?>
      <p class="mt-2 text-sm text-stone"><?= pithead_h($msg) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="mt-8 space-y-4">
      <input type="hidden" name="csrf" value="<?= pithead_h($csrf) ?>" />
      <input type="hidden" name="id" value="<?= (int) $id ?>" />

      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Category
        <select name="category_id" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" required>
          <?php foreach ($cats as $c) : ?>
            <option value="<?= (int) $c['id'] ?>" <?= $row && (int) $row['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= pithead_h((string) $c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Slug
        <input name="slug" required value="<?= pithead_h((string) ($row['slug'] ?? '')) ?>" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Name
        <input name="name" required value="<?= pithead_h((string) ($row['name'] ?? '')) ?>" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Tagline
        <input name="tagline" value="<?= pithead_h((string) ($row['tagline'] ?? '')) ?>" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Short description
        <textarea name="short_description" rows="3" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm"><?= pithead_h((string) ($row['short_description'] ?? '')) ?></textarea>
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Price (GBP)
        <input name="price" type="text" required value="<?= pithead_h($row ? number_format(((int) $row['price_cents']) / 100, 2, '.', '') : '') ?>" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Weight label
        <input name="weight_label" value="<?= pithead_h((string) ($row['weight_label'] ?? '')) ?>" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">SKU
        <input name="sku" value="<?= pithead_h((string) ($row['sku'] ?? '')) ?>" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm" />
      </label>
      <label class="flex items-center gap-2 text-sm font-semibold">
        <input type="checkbox" name="is_active" value="1" <?= !$row || (int) $row['is_active'] ? 'checked' : '' ?> /> Active
      </label>
      <label class="flex items-center gap-2 text-sm font-semibold">
        <input type="checkbox" name="is_featured" value="1" <?= $row && (int) $row['is_featured'] ? 'checked' : '' ?> /> Featured (homepage grid)
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Brew suggestions
        <textarea name="brew_suggestions" rows="4" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm"><?= pithead_h((string) ($row['brew_suggestions'] ?? '')) ?></textarea>
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Specs (JSON object)
        <textarea name="specs" rows="4" class="mt-2 w-full border border-offwhite/30 bg-[#161616] px-3 py-2 text-sm font-mono text-xs"><?php
          if ($row && $row['specs']) {
              $d = json_decode((string) $row['specs'], true);
              echo pithead_h($d ? json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $row['specs']);
          }
        ?></textarea>
      </label>
      <label class="block text-xs font-bold uppercase tracking-widest text-offwhite/70">Primary image (JPEG/PNG/WebP)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full text-sm" />
      </label>
      <?php if ($primaryPath !== '') : ?>
        <p class="text-xs text-offwhite/50">Current: <a href="<?= pithead_h($primaryPath) ?>" class="text-imperial"><?= pithead_h($primaryPath) ?></a></p>
      <?php endif; ?>
      <button type="submit" class="border-2 border-imperial bg-imperial px-6 py-3 text-xs font-bold uppercase tracking-widest hover:bg-coal">Save</button>
    </form>
  </main>
</body>
</html>
