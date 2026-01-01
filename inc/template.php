<?php
/**
 * GLPI 11 - Document Template
 */

$font_family = (!empty($font)) ? $font : 'freesans';
$base_fontsize = (!empty($fontsize)) ? $fontsize . 'pt' : '9pt';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <style>
        @page {
            /* Reservamos espacio al final para que el contenido no choque con las firmas */
            margin: 10mm 15mm 50mm 15mm; 
        }
        
        body {
            font-family: <?= $font_family ?>, sans-serif;
            font-size: <?= $base_fontsize ?>;
            line-height: 1.2;
            margin: 0;
        }
        
        footer {
            position: fixed;
            bottom: -40mm;
            left: 0; right: 0;
            height: 20px;
            text-align: center;
            font-size: 7pt;
            color: #666;
        }

        /* Contenedor de firmas anclado al final */
        .fixed-signature-container {
            position: fixed;
            bottom: -35mm;
            left: 0;
            right: 0;
            width: 100%;
        }

        .table-full {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        /* Styling for user-supplied upper content tables to ensure consistent rendering */
        .upper-content table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            word-wrap: break-word;
            overflow-wrap: break-word;
            margin-bottom: 8px;
        }
        .upper-content table th,
        .upper-content table td {
            border: 0.5px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        .upper-content table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }

        #items {
            border: 0.5px solid black;
            width: 100%;
            border-collapse: collapse;
        }

        #items th, #items td {
            border: 0.5px solid black;
            padding: 4px;
        }

        #items th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .signature-box {
            height: 28mm; 
            border: 0.5px solid black;
            vertical-align: bottom; /* Nombre pegado abajo */
            padding: 0;
            position: relative;
        }

        .sig-label {
            position: absolute;
            top: 4px;
            left: 6px;
            font-weight: bold;
            font-size: 0.85em;
        }

        .sig-name {
            display: block;
            text-align: center;
            font-style: italic;
            font-size: 0.9em;
            width: 100%;
            padding-bottom: 2px;
        }
    </style>
</head>
<body>

<table class="table-full" style="border: 0px;">
    <tr>
        <td style="width: 30%; text-align: center; border: 0px; padding: 5px;">
            <?php if ($islogo == 1 && file_exists($logo)): 
                $img_type = pathinfo($logo, PATHINFO_EXTENSION);
                $img_data = file_get_contents($logo);
                $base64 = 'data:image/' . $img_type . ';base64,' . base64_encode($img_data);
                $max_w = !empty($logo_width) ? intval($logo_width) : 150;
                $max_h = !empty($logo_height) ? intval($logo_height) : 70;
            ?>
                <img src="<?= $base64 ?>" style="max-width: <?= $max_w ?>px; max-height: <?= $max_h ?>px; object-fit: contain;" />
            <?php endif; ?>
        </td>
        <td style="width: 40%; text-align: center; font-size: 14pt; font-weight: bold; border: 0px; vertical-align: middle;">
            <?= htmlspecialchars($title) ?>
        </td>
        <td style="width: 30%; text-align: center; border: 0px; vertical-align: middle;">
            <?= htmlspecialchars($city) ?> <?= date('d/m/Y') ?>
        </td>
    </tr>
</table>

<div class="upper-content" style="margin-top: 10px;">
    <?= $upper_content ?>
</div>

<table id="items">
    <thead>
		<tr>
			<th style="width: 20px;">#</th>
			<th><?= __('Type', 'deliveryterms') ?></th>
			<th><?= __('Manufacturer', 'deliveryterms') ?></th>
			<th><?= __('Model', 'deliveryterms') ?></th>
			<th><?= __('Name', 'deliveryterms') ?></th>
			<?php if ($serial_mode == 1): ?>
				<th><?= __('Serial number', 'deliveryterms') ?></th>
				<th><?= __('Inventory number', 'deliveryterms') ?></th>
			<?php else: ?>
				<th><?= __('Serial number / Inventory', 'deliveryterms') ?></th>
			<?php endif; ?>
			<th><?= __('Comments', 'deliveryterms') ?></th> </tr>
	</thead>
    <tbody>
	<?php 
	$lp = 1;
	if (!empty($number)):
		foreach ($number as $key): ?>
			<tr>
				<td style="text-align: center;"><?= $lp++ ?></td>
				<td><?= htmlspecialchars($type_name[$key] ?? '') ?></td>
				<td><?= htmlspecialchars($man_name[$key] ?? '') ?></td>
				<td><?= htmlspecialchars($mod_name[$key] ?? '') ?></td>
				<td><?= htmlspecialchars($item_name[$key] ?? '') ?></td>
				<?php if ($serial_mode == 1): ?>
					<td><?= htmlspecialchars($serial[$key] ?? '') ?></td>
					<td><?= htmlspecialchars($otherserial[$key] ?? '') ?></td>
				<?php else: 
					$val = !empty($serial[$key]) ? $serial[$key] : ($otherserial[$key] ?? '-'); ?>
					<td><?= htmlspecialchars($val) ?></td>
				<?php endif; ?>
				<td><?= htmlspecialchars($comments[$key] ?? '') ?></td> </tr>
		<?php endforeach; 
	endif; ?>
	</tbody>
</table>

<div style="margin-top: 10px;">
    <?= $content ?>
</div>

<div class="fixed-signature-container">
    <table class="table-full">
        <tr>
            <td class="signature-box" style="width: 50%;">
                <span class="sig-label"><?= __('Administrator', 'deliveryterms') ?>:</span>
                <span class="sig-name">
                    <?= ($author_state == 2) ? htmlspecialchars($author_name) : htmlspecialchars($author) ?>
                </span>
            </td>
            <td class="signature-box" style="width: 50%;">
                <span class="sig-label"><?= __('User', 'deliveryterms') ?>:</span>
                <span class="sig-name">
                    <?= htmlspecialchars($owner) ?>
                </span>
            </td>
        </tr>
    </table>
</div>

<footer><?= htmlspecialchars($footer) ?></footer>

</body>
</html>