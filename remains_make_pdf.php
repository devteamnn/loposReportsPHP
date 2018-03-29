<?php
require_once 'external_scripts/tfpdf/tfpdf.php';
require_once 'markup/remains--make-pdf.php';


//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

function remainsMakePdf($data, $directory, $par) {

  function drawTableHeader(&$par) {
    $col = '<col><col><col><col>';
    $row1 = '<th rowspan="2">№</th><th rowspan="2">Группа</th><th rowspan="2">Наименование</th><th rowspan="2">Количество</th>';
    $row2 = '';

    if (validate_parametr($par, 'p04')) {
      $row1 .= '<th rowspan="2">Текущая цена закупки</th>';
      $col .= '<col>';
    }
    if (validate_parametr($par, 'p05')) {
      $row1 .= '<th rowspan="2">Текущая цена продажи</th>';
      $col .= '<col>';
    }
    if (validate_parametr($par, 'p02')) {
      $row1 .= '<th rowspan="2">На сумму по закупке</th>';
      $col .= '<col>';
    }
    if (validate_parametr($par, 'p03')) {
      $row1 .= '<th rowspan="2">На сумму по продаже</th>';
      $col .= '<col>';
    }
    if (validate_parametr($par, 'p01')) {
      $row1 .= '<th colspan="2">Доставка</th>';
      $row2 .= '<th>Ожидает<br>поступления на<br>склад</th>';
      $row2 .= '<th>Ожидает<br>отправки<br>получателю</th>';
      $col .= '<col>';
    }

    $row = $row1 . '</tr><tr>' . $row2;

    return ['col' => $col, 'header' => $row];
  }

  function drawData(&$htmlDoc, &$data, &$par) {

    $total = [
      'count'=>0,
      'totalPurchase'=>0,
      'totalSell'=>0,
      'cntDlvr1'=>0,
      'cntDlvr2'=>0
    ];

    foreach ($data['content'] as $group) {
      $groupTotal = [
        'count'=>0,
        'totalPurchase'=>0,
        'totalSell'=>0,
        'cntDlvr1'=>0,
        'cntDlvr2'=>0
      ];

      $htmlDoc .= markupDrawGroup($group['group_name'], $par);

      foreach ($group['group_content'] as $key => $row) {
        calcVariableCol($row);

        $htmlDoc .= drawRow($key + 1, $row, $par);
        calcGroupTotal($groupTotal, $row);

      }
      $htmlDoc .= drawGroupTotal($groupTotal, $par);
      calcTotal($total, $groupTotal);
    }

    return $total;
  }

  function drawTotal(&$total, &$par) {
    $row = markupGroupTotal($grpTotal['count']);

    if (validate_parametr($par, 'p02')) {
      $row .= '<td class="subtotal-col">' . $grpTotal['totalPurchase'] . '</td>';
    }
    if (validate_parametr($par, 'p03')) {
     $row .= '<td class="subtotal-col">' . $grpTotal['totalSell'] . '</td>';
    }
    if (validate_parametr($par, 'p01')) {
      $row .= '<td class="subtotal-col">' . $grpTotal['cntDlvr1'] . '</td>';
      $row .= '<td class="subtotal-col">' . $grpTotal['cntDlvr2'] . '</td>';
    }

    $row .= '</tr>';

    return $row;
  }

  function writeFile($name, $directory, &$htmlCode) {
    $tmpName = 'make_reports/temp/' . $name . '.html';
    $fileName = 'users/' . $directory . '/reports/' . $name;

    $file = fopen($tmpName, 'w+');
    fputs($file, $htmlCode);
    fclose($file);

    $cmd = 'external_scripts/wkhtmltox/bin/wkhtmltopdf --encoding utf-8 -O Landscape '
      . $tmpName . ' ' . $fileName ;

    exec($cmd);
    // unlink($tmpName);

    if (file_exists($fileName)) {
      return true;
    }

    return false;
  }

  // ----------------------------------------

  function calcVariableCol(&$row) {

    if (isset($row['price_purchase'])) {
      $row['purchase_sum'] = $row['good_count'] * $row['price_purchase'];
    }

    if (isset($row['price_sell'])) {
      $row['sell_sum'] = $row['good_count'] * $row['price_sell'];
    }
  }

  function calcGroupTotal(&$total, &$row) {

    $total['count'] += (isset($row['good_count'])) ?
         $row['good_count'] : 0;

    $total['totalPurchase'] += (isset($row['purchase_sum'])) ?
         $row['purchase_sum'] : 0;

    $total['totalSell'] += (isset($row['sell_sum'])) ?
         $row['sell_sum'] : 0;

    $total['cntDlvr1'] += (isset($row['count_delivery_1'])) ?
         $row['count_delivery_1'] : 0;

    $total['cntDlvr2'] += (isset($row['count_delivery_2'])) ?
         $row['count_delivery_2'] : 0;
  }

  function calcTotal(&$total, &$groupTotal) {
    $total['count'] += $groupTotal['count'];
    $total['totalPurchase'] += $groupTotal['totalPurchase'];
    $total['totalSell'] += $groupTotal['totalSell'];
    $total['cntDlvr1'] += $groupTotal['cntDlvr1'];
    $total['cntDlvr2'] += $groupTotal['cntDlvr2'];
  }

  function drawRow($index, $rowData, &$par) {
    $row = '<tr><td class="col">' . $index . '</td>';

    $var = isset($rowData['barcode']) ?  $rowData['barcode'] : '';
    $row .= '<td class="col">' . $var . '</td>';

    $row .= '<td class="col">' . $rowData['good_name'] . '</td>';

    $var = (float) number_format((float) $rowData['good_count'], 2, ',', '');
    $row .= '<td class="col">' . $var . '</td>';

    if (validate_parametr($par, 'p04')) {
      $var = isset($rowData['price_purchase']) ?
        (float) number_format((float) $rowData['price_purchase'], 2, ',', '') : '';

      $row .= '<td class="col">' . $var . '</td>';
    }

    if (validate_parametr($par, 'p05')) {
      $var = isset($rowData['price_sell']) ?
        (float) number_format((float) $rowData['price_sell'], 2, ',', '') : '';

      $row .= '<td class="col">' . $var . '</td>';
    }

    if (validate_parametr($par, 'p02')) {
      $var = isset($rowData['purchase_sum']) ?
        (float) number_format((float) $rowData['purchase_sum'], 2, ',', '') : '';

      $row .= '<td class="col">' . $var . '</td>';
    }

    if (validate_parametr($par, 'p03')) {
      $var = isset($rowData['sell_sum']) ?
        (float) number_format((float) $rowData['sell_sum'], 2, ',', '') : '';

      $row .= '<td class="col">' . $var . '</td>';
    }

    if (validate_parametr($par, 'p01')) {
      $var = isset($rowData['count_delivery_1']) ?
        (float) number_format((float) $rowData['count_delivery_1'], 2, ',', '') : '';
      $row .= '<td class="col">' . $var . '</td>';

      $var = isset($rowData['count_delivery_2']) ?
        (float) number_format((float) $rowData['count_delivery_2'], 2, ',', '') : '';
      $row .= '<td class="col">' . $var . '</td>';
    }

    $row .= '</tr>';

    return $row;
  }

  function drawGroupTotal(&$grpTotal, &$par) {
    $row = markupGroupTotal($grpTotal['count']);

    if (validate_parametr($par, 'p02')) {
      $row .= '<td class="subtotal-col">' . $grpTotal['totalPurchase'] . '</td>';
    }
    if (validate_parametr($par, 'p03')) {
     $row .= '<td class="subtotal-col">' . $grpTotal['totalSell'] . '</td>';
    }
    if (validate_parametr($par, 'p01')) {
      $row .= '<td class="subtotal-col">' . $grpTotal['cntDlvr1'] . '</td>';
      $row .= '<td class="subtotal-col">' . $grpTotal['cntDlvr2'] . '</td>';
    }

    $row .= '</tr>';

    return $row;
  }

  function getFileName($prefix, $date, $type) {
    function getRnd() {
      $genName = '';
      for ($i = 0; $i < 4; $i++) {
       $gen = rand(0, 9);
       $genName .= $gen;
      }
      return $genName;
    }
    //    -----------------------------
    $date = date('d_m', $date);
    $name = $prefix . '_' . $date . '_';

    $count = 0;

    do {
      $number = getRnd();
      $name .= $number;
      $count ++;

      if ($count > 100000) {
        break;
      }

    } while (file_exists($name));

    $name .= '.' . $type;

    return $name;
  }

  // ---------- MAIN ----------
  $date = date("Y-m-d H:i:s", $data['current_time']);
  $htmlDoc = markupDrawDocHeader($data['business_name'], $data['stock_name'],
    $date);

  $tableHeader = drawTableHeader($par);

  $htmlDoc .= $tableHeader['col'];
  $htmlDoc .= markupDrawTableHeaderStart();
  $htmlDoc .= $tableHeader['header'];
  $htmlDoc .= markupDrawTableHeaderEnd();

  $total = drawData($htmlDoc, $data, $par);

  $htmlDoc .= markupTotal($total['count'], $total['totalPurchase'],
    $total['totalSell'], $total['cntDlvr1'], $total['cntDlvr2']);

  $name = getFileName('remains', $data['current_time'], 'pdf');

  if (writeFile($name, $directory, $htmlDoc)) {
    return $name;
  }

  return false;
}
