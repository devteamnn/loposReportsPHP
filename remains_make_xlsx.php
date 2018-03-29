<?php
require_once 'external_scripts/xlsxwriter.class.php';

//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

function remainsMakeXlsx($data, $directory, $par) {  
  //  -------- Функции, формирующие документ --------
  function setupHeader(&$writer) {
    $header = array(
      ' '=>'@',
      '  '=>'@',
      '   '=>'@',
      '    '=>'@',
      '     '=>'@',
      '      '=>'@',
      '       '=>'@',
      '        '=>'@',
      '         '=>'@',
      '          '=>'@'
    );
    
    $col_options = ['widths'=>[6, 18, 24, 12, 12, 12, 12, 12, 12, 12]];

    $writer->writeSheetHeader('Sheet1', $header, $col_options);
  }
  
  function drawInfo(&$writer, $business, $stock) {
    $rowOptions = ['height'=>30, 'valign'=>'center', 'halign'=>'center'];

    $rowOptionsDate = array(
      'height'=>30,
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'],
       ['valign'=>'center', 'halign'=>'center'] 
    );

    drawBusiness($writer, $business, $rowOptions);
    drawStock($writer, $stock, $rowOptions);
    drawSpace($writer);
    drawDate($writer, $rowOptionsDate);
  }
  
  function drawTableHeader(&$writer, $par) {
    $rowOptions = array(
      'height'=>40,
     
      ['valign'=>'center', 'halign'=>'center', 'wrap_text'=> true,
          'fill'=>'#D3FEE8', 'font-style'=>'bold', 'border'=>'left,right,top,bottom'],
      ['valign'=>'center', 'halign'=>'center', 'wrap_text'=> true,
          'fill'=>'#D3FEE8', 'font-style'=>'bold', 'border'=>'top,right,bottom'],
      ['valign'=>'center', 'halign'=>'center', 'wrap_text'=> true,
          'fill'=>'#D3FEE8', 'font-style'=>'bold', 'border'=>'top,right,bottom'],
      ['valign'=>'center', 'halign'=>'center', 'wrap_text'=> true,
          'fill'=>'#D3FEE8', 'font-style'=>'bold', 'border'=>'top,right,bottom'],
    );

    for ($i = 1; $i < 6; $i++) {
      if (validate_parametr($par, 'p0' . $i)) {
        $rowOptions[] = ['valign'=>'center', 'halign'=>'center',
          'wrap_text'=> true, 'fill'=>'#D3FEE8', 'font-style'=>'bold',
          'border'=>'top,right,bottom'];
        if ($i === 1) {
          $rowOptions[] = ['valign'=>'center', 'halign'=>'center',
          'wrap_text'=> true, 'fill'=>'#D3FEE8', 'font-style'=>'bold',
          'border'=>'top,right,bottom'];
        }
      }
    }
     
    $coolsRow2 = ['','','',''];
    $coolsRow1 = ['№', 'Группа', 'Наименование', 'Количество'];
    
    $coolsCount = 4;
    
    if (validate_parametr($par, 'p04')) {
      $coolsRow2[] = '';
      $coolsRow1[] = 'Текущая цена закупки:';
      $coolsCount++;
    }
    
    if (validate_parametr($par, 'p05')) {
      $coolsRow2[] = '';
      $coolsRow1[] = 'Текущая цена продажи:';
      $coolsCount++;
    }
    
    if (validate_parametr($par, 'p02')) {
      $coolsRow2[] = '';
      $coolsRow1[] = 'На сумму по закупке:';
      $coolsCount++;
    }
    
    if (validate_parametr($par, 'p03')) {
      $coolsRow2[] = '';
      $coolsRow1[] = 'На сумму по продаже:';
      $coolsCount++;
    }
    
    if (validate_parametr($par, 'p01')) {
      $coolsRow1[] = 'Доставка';
      $coolsRow1[] = '';
      $coolsRow2[] = 'ожидает поступления на склад:';
      $coolsRow2[] = 'ожидает отправки покупателю';
      
      $writer->markMergedCell('Sheet1', $start_row=5, $start_col=$coolsCount,
        $end_row=5, $end_col=$coolsCount + 1);
    }
    
    for ($i = 0; $i < $coolsCount; $i++) {
      $writer->markMergedCell('Sheet1', $start_row=5, $start_col=$i,
          $end_row=6, $end_col=$i);
    }  
    
    $writer->writeSheetRow('Sheet1',$coolsRow1, $rowOptions);
    $writer->writeSheetRow('Sheet1',$coolsRow2, $rowOptions);
  }

  function drawData(&$writer, $data, $par) {

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

      drawGroupName($writer, $group['group_name'], $par);

      foreach ($group['group_content'] as $key => $row) {
        calcVariableCol($row);

        drawRow($writer, $key + 1, $row, $par);
        calcGroupTotal($groupTotal, $row);

      }
      drawGroupTotal($writer, $groupTotal, $par);
      calcTotal($total, $groupTotal);
    }

    return $total;
  }

  function drawTotal(&$writer, $total, $par) {
    $rowOptions = array(
      'height'=>20,
      ['valign'=>'center', 'halign'=>'center'],
      ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold',
        'border'=>'left,top,bottom', 'fill'=>'#D3FEE8', 'color'=>'#004200'],
      ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold',
        'border'=>'top,bottom', 'fill'=>'#D3FEE8', 'color'=>'#004200']
    );
    
    for ($i = 1; $i < 6; $i++) {
      if (validate_parametr($par, 'p0' . $i)) {
        $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold',
        'border'=>'top,bottom', 'fill'=>'#D3FEE8', 'color'=>'#004200'];      
        if ($i === 1) {
          $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold',
        'border'=>'top,bottom', 'fill'=>'#D3FEE8', 'color'=>'#004200'];        
        }
      }
    }
    
    $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold',
        'border'=>'right,top,bottom', 'fill'=>'#D3FEE8', 'color'=>'#004200'];
    
    $cools = array(
      '',
      'Итого:',
      '',
      number_format((float) $total['count'] , 2, ',', '')
    );
    
    if (validate_parametr($par, 'p04')) {
      $cools[] = '';
    }
    if (validate_parametr($par, 'p05')) {
      $cools[] = '';
    }
    if (validate_parametr($par, 'p02')) {
      $cools[] = number_format((float) $total['totalPurchase'] , 2, ',', '');
    }
    if (validate_parametr($par, 'p03')) {
      $cools[] = number_format((float) $total['totalSell'] , 2, ',', '');
    }
    if (validate_parametr($par, 'p01')) {
      $cools[] = number_format((float) $total['cntDlvr1'] , 2, ',', '');
      $cools[] = number_format((float) $total['cntDlvr2'] , 2, ',', '');
    }
    
    $writer->writeSheetRow(
      'Sheet1',
      $cools,
      $rowOptions
    );
  }
  
  //  -------- Вспомогательные функции --------
  function drawSpace(&$writer) {
    $writer->writeSheetRow(
      'Sheet1',
      ['', '', '', '', '', '', '', '', '', '']
    );
  }
  
  function drawBusiness(&$writer, &$business, &$rowOptions) {
    $writer->writeSheetRow(
      'Sheet1',
      ['', 'Предприятие', $business, '', '', '', '', '', '', ''],
      $rowOptions
    );
  }
    
  function drawStock(&$writer, &$stock, &$rowOptions) {
    $writer->writeSheetRow(
      'Sheet1',
      ['', 'Точка продажи:', $stock, '', '', '', '', '', '', ''],
      $rowOptions
    );
  }
  
  function drawDate(&$writer,$rowOptions) {
    $today = date("Y-m-d H:i:s");

    $writer->writeSheetRow(
      'Sheet1',
      ['', '', 'Остатки товара', $today, '', '', '', '', '', ''],
      $rowOptions
    );
    $writer->markMergedCell('Sheet1', 4, 3, 4, 4);
  }
  
  function drawGroupName(&$writer, $groupName, $par) {
    $rowOptions = array(
      'height'=>20,
      ['valign'=>'center', 'halign'=>'center', 'fill'=>'#E5E5E5',
        'border'=>'bottom, left'],
      ['valign'=>'center', 'halign'=>'center', 'fill'=>'#E5E5E5',
        'border'=>'bottom'],
      ['valign'=>'center', 'halign'=>'center', 'fill'=>'#E5E5E5',
        'border'=>'bottom']
    );
    
    $cools = ['', $groupName, '',''];
    
    for ($i = 1; $i < 6; $i++) {
      if (validate_parametr($par, 'p0' . $i)) {
        $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'fill'=>'#E5E5E5',
        'border'=>'bottom'];      
        $cools[] = '';

        if ($i === 1) {
          $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'fill'=>'#E5E5E5',
            'border'=>'bottom'];        
          $cools[] = '';
        }
      }
    }

    $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'fill'=>'#E5E5E5',
      'border'=>'bottom,right'];
     
    $writer->writeSheetRow(
      'Sheet1',
      $cools,
      $rowOptions
    );
  }
  
  function drawRow(&$writer, $index, $row, $par) {
     $rowOptions = 
     [
      'height'=>20,
      ['valign'=>'center', 'halign'=>'center', 'border'=>'left,right'],
      ['valign'=>'center', 'halign'=>'center', 'border'=>'right'],
      ['valign'=>'center', 'halign'=>'center', 'border'=>'right'],
      ['valign'=>'center', 'halign'=>'center', 'border'=>'right']
    ];
     
    for ($i = 1; $i < 6; $i++) {
      if (validate_parametr($par, 'p0' . $i)) {
        $rowOptions[] = ['valign'=>'center', 'halign'=>'center',
          'border'=>'right'];      
        if ($i === 1) {
          $rowOptions[] = ['valign'=>'center', 'halign'=>'center',
            'border'=>'right'];        
        }
      }
    }
    
    $cools = array(
      $index,
      (isset($row['barcode'])) ? $row['barcode'] : '',
        $row['good_name'],
        number_format((float) $row['good_count'], 2, ',', '')
    );
    
    if (validate_parametr($par, 'p04')) {
      $cools[] = (isset($row['price_purchase'])) ? number_format((float) $row['price_purchase'] , 2, ',', '') : '';
    }
    if (validate_parametr($par, 'p05')) {
      $cools[] = (isset($row['price_sell'])) ? number_format((float) $row['price_sell'] , 2, ',', '') : '';
    }
    if (validate_parametr($par, 'p02')) {
      $cools[] = (isset($row['purchase_sum'])) ? number_format((float) $row['purchase_sum'] , 2, ',', '') : '';
    }
    if (validate_parametr($par, 'p03')) {
      $cools[] = (isset($row['sell_sum'])) ? number_format((float) $row['sell_sum'] , 2, ',', '') : '';
    }
    if (validate_parametr($par, 'p01')) {
      $cools[] = (isset($row['count_delivery_1'])) ? number_format((float) $row['count_delivery_1'] , 2, ',', '') : '';
      $cools[] = (isset($row['count_delivery_2'])) ? number_format((float) $row['count_delivery_2'] , 2, ',', '') : '';
    }
    
    $writer->writeSheetRow(
      'Sheet1',
      $cools,
      $rowOptions
    );
  }
  
  function drawGroupTotal(&$writer, $totalGrp, $par) {
    $rowOptions = array(
      'height'=>20,
      ['valign'=>'center', 'halign'=>'center', 'border'=>'left,top,bottom',
        'color'=>'#004200'],
      ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold', 
        'border'=>'top,bottom', 'color'=>'#004200'],
      ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold', 
        'border'=>'top,bottom', 'color'=>'#004200'], 
    );
    
    for ($i = 1; $i < 6; $i++) {
      if (validate_parametr($par, 'p0' . $i)) {
        $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold', 
        'border'=>'top,bottom', 'color'=>'#004200'];      
        if ($i === 1) {
          $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold', 
        'border'=>'top,bottom', 'color'=>'#004200'];        
        }
      }
    }
    
    $rowOptions[] = ['valign'=>'center', 'halign'=>'center', 'font-style'=>'bold', 
        'border'=>'right,top,bottom', 'color'=>'#004200'];
         
    $cools = ['', 'Подытог', '', number_format((float) $totalGrp['count'] , 2, ',', '')];
    
    if (validate_parametr($par, 'p04')) {
      $cools[] = '';
    }
    if (validate_parametr($par, 'p05')) {
      $cools[] = '';
    }
    if (validate_parametr($par, 'p02')) {
      $cools[] = number_format((float) $totalGrp['totalPurchase'] , 2, ',', '');
    }
    if (validate_parametr($par, 'p03')) {
      $cools[] = number_format((float) $totalGrp['totalSell'] , 2, ',', '');
    }
    if (validate_parametr($par, 'p01')) {
      $cools[] = number_format((float) $totalGrp['cntDlvr1'] , 2, ',', '');
      $cools[] = number_format((float) $totalGrp['cntDlvr2'] , 2, ',', '');
    }
    
    $writer->writeSheetRow(
      'Sheet1',
      $cools,
      $rowOptions
    );
  }
  
  function calcTotal(&$total, &$groupTotal) {
    $total['count'] += $groupTotal['count'];
    $total['totalPurchase'] += $groupTotal['totalPurchase'];
    $total['totalSell'] += $groupTotal['totalSell'];
    $total['cntDlvr1'] += $groupTotal['cntDlvr1'];
    $total['cntDlvr2'] += $groupTotal['cntDlvr2'];   
  }


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
//  -------- MAIN :) --------

  $writer = new XLSXWriter();

  setupHeader($writer);
  
  $stock = (isset($data['stock_name'])) ? $data['stock_name'] : '';
  
  drawInfo($writer, $data['business_name'], $stock);
  drawTableHeader($writer, $par);
  $total = drawData($writer, $data, $par);
  drawSpace($writer);
  drawTotal($writer, $total, $par);
  
  $name = getFileName('Remains', $data['current_time'], 'xlsx');
      
  $writer->writeToFile('users/' . $directory . '/reports/' . $name);

  return $name;
}

