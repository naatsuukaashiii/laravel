<?php
namespace App\Services;
use App\Models\User;
use App\Models\Role;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportService
{
    /**
     * Import users from Excel file
     *
     * @param string $filePath
     * @param string $mode
     * @return array
     */
    public function importUsers(string $filePath, string $mode): array
    {
        $config = Config::get('export.users');
        $model = new User();
        
        return $this->importFromExcel(
            filePath: $filePath,
            model: $model,
            columns: $config['columns'],
            mode: $mode,
            additionalData: [
                'password' => Hash::make($config['default_password'])
            ],
            validationRules: [
                'username' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'birthday' => 'nullable|date',
            ]
        );
    }

    /**
     * Import roles from Excel file
     *
     * @param string $filePath
     * @param string $mode
     * @return array
     */
    public function importRoles(string $filePath, string $mode): array
    {
        $config = Config::get('export.roles');
        $model = new Role();
        
        return $this->importFromExcel(
            filePath: $filePath,
            model: $model,
            columns: $config['columns'],
            mode: $mode,
            validationRules: [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:roles,code',
                'description' => 'nullable|string',
            ]
        );
    }

    /**
     * Generic method to import data from Excel
     *
     * @param string $filePath
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $columns
     * @param string $mode
     * @param array $additionalData
     * @param array $validationRules
     * @return array
     */
    protected function importFromExcel(
        string $filePath,
        $model,
        array $columns,
        string $mode,
        array $additionalData = [],
        array $validationRules = []
    ): array {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        array_shift($rows);
        $results = [];
        
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                try {
                    $data = array_combine(
                        array_keys($columns),
                        $row
                    );

                    if (!empty($validationRules)) {
                        $validator = Validator::make($data, $validationRules);
                        if ($validator->fails()) {
                            $results[] = sprintf(
                                'Row %d: Validation failed - %s',
                                $index + 1,
                                implode(', ', $validator->errors()->all())
                            );
                            continue;
                        }
                    }

                    $data = $this->processData($data);
                    
                    $existingRecord = $model->find($data['id']);
                    if ($existingRecord) {
                        if ($mode === 'update') {
                            $updateData = array_diff_key($data, ['id' => '']);
                            $existingRecord->update($updateData);
                            $results[] = sprintf(
                                Config::get('export.messages.success.update'),
                                $index + 1,
                                $data['id']
                            );
                        } else {
                            $results[] = sprintf(
                                Config::get('export.messages.error.duplicate'),
                                $index + 1,
                                $data['id']
                            );
                        }
                    } else {
                        $model->create(array_merge($data, $additionalData));
                        $results[] = sprintf(
                            Config::get('export.messages.success.create'),
                            $index + 1,
                            $data['id']
                        );
                    }
                } catch (\Exception $e) {
                    $results[] = sprintf(
                        'Row %d: Error - %s',
                        $index + 1,
                        $e->getMessage()
                    );
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $results[] = sprintf(
                Config::get('export.messages.error.unknown'),
                $index + 1
            );
        }
        
        return $results;
    }

    /**
     * Process data before saving
     *
     * @param array $data
     * @return array
     */
    protected function processData(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        if (isset($data['birthday']) && $data['birthday']) {
            try {
                $data['birthday'] = date('Y-m-d', strtotime($data['birthday']));
            } catch (\Exception $e) {
                $data['birthday'] = null;
            }
        }

        return $data;
    }
}