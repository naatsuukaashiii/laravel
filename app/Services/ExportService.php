<?php
namespace App\Services;
use App\Models\User;
use App\Models\Role;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Export users to Excel file
     *
     * @return string Path to the exported file
     */
    public function exportUsers(): string
    {
        $users = User::all();
        $config = Config::get('export.users');
        
        return $this->exportToExcel(
            data: $users,
            columns: $config['columns'],
            filePath: $config['file_path']
        );
    }

    /**
     * Export roles to Excel file
     *
     * @return string Path to the exported file
     */
    public function exportRoles(): string
    {
        $roles = Role::all();
        $config = Config::get('export.roles');
        
        return $this->exportToExcel(
            data: $roles,
            columns: $config['columns'],
            filePath: $config['file_path']
        );
    }

    /**
     * Generic method to export data to Excel
     *
     * @param \Illuminate\Database\Eloquent\Collection $data
     * @param array $columns
     * @param string $filePath
     * @return string
     */
    protected function exportToExcel($data, array $columns, string $filePath): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set headers
        $column = 'A';
        foreach ($columns as $field => $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        // Fill data
        $row = 2;
        foreach ($data as $item) {
            $column = 'A';
            foreach ($columns as $field => $header) {
                $value = $item->$field;
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d');
                }
                $sheet->setCellValue($column . $row, $value);
                $column++;
            }
            $row++;
        }

        // Ensure directory exists
        $fullPath = storage_path('app/' . $filePath);
        Storage::makeDirectory(dirname($filePath));

        // Save file
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }
}