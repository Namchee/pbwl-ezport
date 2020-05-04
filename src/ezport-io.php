<?php
    require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
     
    // import the required namespaces
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Writer\Xls;
    use PhpOffice\PhpSpreadsheet\Style;

    ob_start(); // start the output buffer

    /**
     * Export the data in the desired format
     */
    function ezport_export_data($data, $filename, $format) {
        switch ($format) {
            case 'csv':
                ezport_export_as_csv($data, $filename);
                break;
            case 'xls':
                ezport_export_as_xls($data, $filename);
                break;
            case 'xlsx':
                ezport_export_as_xlsx($data, $filename);
                break;
            default:
                echo 'Unsupported format';
                exit();
        }
    }

    /**
     * Export the data as CSV file
     */
    function ezport_export_as_csv($data, $filename) {
        ob_clean(); // clean the output buffer first

        header("Content-Type: 'text/csv'; charset=utf-8"); // Define content
    	header("Content-Disposition: attachment; filename=${filename}.csv"); // Define attachment
		header("Cache-Control: no-cache, no-store, must-revalidate"); // Disable caching HTTP 1.1
		header("Pragma: no-cache"); // Disable caching HTTP 1.0
        header("Expires: 0"); // Proxies

        $output_file = fopen('php://output', 'w');

        foreach ($data as $entry) {
            fputcsv($output_file, $entry);
        }

        ob_end_flush();
        fclose($output_file);
    }

    /**
     * Export the data as XLS (prior Excel 2007) file
     */
    function ezport_export_as_xls($data, $filename) {
        ob_clean(); // clean the output buffer first

        header("Content-Type: 'application/vnd.ms-excel'; charset=utf-8"); // Define content
    	header("Content-Disposition: attachment; filename=${filename}.xls"); // Define attachment
		header("Cache-Control: no-cache, no-store, must-revalidate"); // Disable caching HTTP 1.1
		header("Pragma: no-cache"); // Disable caching HTTP 1.0
        header("Expires: 0"); // Proxies

        // Header formatting
        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setTitle("EZPort - Shop Orders"); // set the worksheet name
        
        $worksheet->fromArray($data, NULL); // fill the value
        
        $highestColumn = $worksheet->getHighestColumn(); // get the farthest column
            
        $worksheet->getStyle("A1:${highestColumn}1")->applyFromArray($styleArray); // bold the headers

        foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet
                ->getColumnDimension($column->getColumnIndex())
                ->setAutoSize(true);
        }
        
        $writer = new Xls($spreadsheet);

		ob_end_clean(); // prevent XLS corruption

        $writer->save('php://output'); // save it
    }

    /**
     * Export the data as XLSX (Excel 2007) file
     */
    function ezport_export_as_xlsx($data, $filename) {
        ob_clean(); // clean the output buffer first

        header("Content-Type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; charset=utf-8"); // Define content
    	header("Content-Disposition: attachment; filename=${filename}.xlsx"); // Define attachment
		header("Cache-Control: no-cache, no-store, must-revalidate"); // Disable caching HTTP 1.1
		header("Pragma: no-cache"); // Disable caching HTTP 1.0
        header("Expires: 0"); // Proxies

        // Header formatting
        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setTitle("EZPort - Shop Orders"); // set the worksheet name
        
        $worksheet->fromArray($data, NULL); // fill the value
        
        $highestColumn = $worksheet->getHighestColumn(); // get the farthest column
            
        $worksheet->getStyle("A1:${highestColumn}1")->applyFromArray($styleArray); // bold the headers

        // Autoresize all columns
        foreach ($worksheet->getColumnIterator() as $column) {
            $worksheet
                ->getColumnDimension($column->getColumnIndex())
                ->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
		
		ob_end_clean(); // prevent XLSX corruption

        $writer->save('php://output'); // save it
    }
?>