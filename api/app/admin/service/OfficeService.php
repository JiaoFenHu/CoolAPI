<?php
declare(strict_types=1);

namespace app\admin\service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

use repository\BaseService;
use repository\exception\CoolApiException;

class OfficeService extends BaseService
{
    private string $sheet_title = 'sheetReport';
    private string $horizontal = 'center';
    private string $vertical = 'center';
    private array $columnWidth = [];

    /**
     * 设置数据的水平方向
     * @param string $direction
     * @return bool
     * @throws CoolApiException
     */
    function set_horizontal(string $direction): bool
    {
        $direction = strtolower($direction);
        if (in_array($direction, ['left', 'right', 'center'])) {
            $this->horizontal = $direction;
            return true;
        }
        throw new CoolApiException("表格数据水平方向参数错误！");
    }

    /**
     * 获取水平配置
     * @param string $position
     * @return string
     */
    function get_horizontal(string $position = ''): string
    {
        $position = empty($position) ? $this->vertical : $position;
        switch ($position) {
            case "left":
                return \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT;
                break;
            case "right":
                return \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT;
                break;
            default:
                return \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
                break;
        }
    }

    /**
     * 设置数据的垂直方向
     * @param string $direction
     * @return bool
     * @throws CoolApiException
     */
    function set_vertical(string $direction): bool
    {
        $direction = strtolower($direction);
        if (in_array($direction, ['top', 'bottom', 'center'])) {
            $this->vertical = $direction;
            return true;
        }
        throw new CoolApiException("表格数据垂直方向参数错误！");
    }

    /**
     * 获取垂直配置
     * @param string $position
     * @return string
     */
    function get_vertical(string $position = ''): string
    {
        $position = empty($position) ? $this->vertical : $position;
        switch ($position) {
            case "top":
                return \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP;
                break;
            case "bottom":
                return \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM;
                break;
            default:
                return \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;
                break;
        }
    }

    /**
     * 设置表格宽度
     * @param array $columns
     * @return bool
     */
    function set_column_width(array $columns): bool
    {
        $before = "A";
        foreach ($columns as $column_width) {
            $this->columnWidth[$before] = $column_width;
            $before++;
        }
        return true;
    }

    /**
     * 设置sheet页标题
     * @param array $title
     */
    function set_sheet_title(array $title): void
    {
        $this->sheet_title = $title;
    }

    /**
     * 表格样式设置
     * @param array $config
     * @return array[]
     */
    function style_setting(array $config = []): array
    {
        $setting = [
            'font' => [
                'name' => 'Arial',
                'bold' => false,
                'size' => 11,
                'color' => [
                    'argb' => 'FF000000'
                ]
            ]
        ];

        if (!empty($config)) {
            if (isset($config['font.bold'])) $setting['font']['bold'] = $config['font.bold'];
            if (isset($config['font.size'])) $setting['font']['size'] = $config['font.size'];
            if (isset($config['font.color'])) $setting['font']['color']['argb'] = $config['font.color'];

            // 水平位置
            if (isset($config['alignment.horizontal.position'])) {
                $setting['alignment']['horizontal'] = $this->get_horizontal($config['alignment.horizontal.position']);
            }
            // 垂直位置
            if (isset($config['alignment.vertical.position'])) {
                $setting['alignment']['vertical'] = $this->get_vertical($config['alignment.vertical.position']);
            }

            // 填充背景颜色
            if (isset($config['fill.startColor'])) {
                $setting['fill'] = [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => $config['fill.startColor'],
                    ]
                ];
            }
        }
        return $setting;
    }

    /**
     * 针对单行表格设置样式
     * @param string $level
     * @return array[]
     */
    function set_row_style(string $level = 'empty'): array
    {
        $level = strtolower($level);
        switch ($level) {
            case "enable":
            case "success":
                $set = [
                    'fill.startColor' => 'FF3CB371',
                ];
                break;
            case "warning":
                $set = [
                    'fill.startColor' => 'FFFFD700'
                ];
                break;
            case "error":
                $set = [
                    'fill.startColor' => 'FFCD5C5C'
                ];
                break;
            case "disable":
                $set = [
                    'fill.startColor' => 'FF808080'
                ];
                break;
            default:
                $set = [];
                break;
        }
        return $this->style_setting($set);
    }

    /**
     * 转换格式配置
     * @param array $config
     * @return array
     */
    function setting_conversion(array $config): array
    {
        $return_config = [];
        is_array($config) or die("配置参数错误！");
        if (isset($config['name'])) $return_config['font.name'] = $config['name'];
        if (isset($config['size'])) $return_config['font.size'] = $config['size'];
        if (isset($config['bold'])) $return_config['font.bold'] = $config['bold'];
        if (isset($config['color'])) $return_config['font.color'] = $config['color'];
        if (isset($config['hPosition'])) $return_config['alignment.horizontal.position'] = $config['hPosition'];
        if (isset($config['vPosition'])) $return_config['alignment.vertical.position'] = $config['vPosition'];
        if (isset($config['bg_color'])) $return_config['fill.startColor'] = $config['bg_color'];
        return $return_config;
    }

    /**
     * 获取默认的标题格式
     * @param string $color
     * @param array $conf
     * @return array[]
     */
    function get_default_title_setting(string $color, array $conf = []): array
    {
        switch ($color) {
            case "green":
                $conf = [
                    'color' => 'FFFFFFFF',
                    'bg_color' => 'FF2E8B57',
                ];
                break;
            case "blue":
                $conf = [
                    'color' => 'FFFFFFFF',
                    'bg_color' => 'FF4169E1',
                ];
                break;
            case "gray":
                $conf = [
                    'color' => 'FFFFFFFF',
                    'bg_color' => 'FF2F4F4F',
                ];
                break;
        }
        return $this->style_setting($this->setting_conversion(array_merge(['size' => 14, 'bold' => true], $conf)));
    }

    /**
     * 验证表头跟表数据数量是否一致
     * @param array $title
     * @param array $data
     * @return bool
     */
    function check_excel_column(array $title, array $data): bool
    {
        if (!is_array($title)) return false;
        foreach ($data as $value) {
            if (!is_array($value)) return false;
            unset($value['style']);
            if (count($value) !== count($title)) return false;
        }
        return true;
    }

    /**
     * 统一处理导出功能
     * @param array $data
     * @param array $titles
     * @param string $title_style
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    function export(array $data, array $titles = [], string $title_style = "") : Spreadsheet
    {
        // $this->check_excel_column($titles, $data) or die("标头与表格数据列数不对应！");

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("hjiaofen")
            ->setLastModifiedBy("hjiaofen")
            ->setTitle("Office 2007 XLSX Business Report")
            ->setSubject("Office 2007 XLSX Business Report")
            ->setDescription("Business Report for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $spreadsheet->setActiveSheetIndex(0);
        $office = $spreadsheet->getActiveSheet();
        $office->setTitle($this->sheet_title);

        if (!empty($titles)) {
            $conf = [];
            $before = "A";
            foreach ($titles as $key => $title) {
                if (!isset($after)) {
                    $after = $before;
                } else {
                    $after++;
                }

                if (is_numeric($key)) {
                    $realTitle = $title;
                } else {
                    $realTitle = $key;
                    $conf[$after] = $this->style_setting($this->setting_conversion($title));
                }

                if (isset($this->columnWidth[$after])) {
                    $office->getColumnDimension($after)->setWidth($this->columnWidth[$after]);
                }
                $office->getStyle($after)->applyFromArray($this->style_setting($this->setting_conversion([
                    'hPosition' => $this->horizontal,
                    'vPosition' => $this->vertical
                ])));
                $office->setCellValue("{$after}1", $realTitle);
            }

            // 针对标题配置格式
            $office->getStyle("{$before}1:{$after}1")->applyFromArray($this->get_default_title_setting($title_style));
            if (!empty($conf)) {
                // 针对单个单元格标头设置格式
                foreach ($conf as $key => $values) {
                    $office->getStyle("{$key}1")->applyFromArray($values);
                }
            }
        }

        // 开始导入数据
        $cBegin = "A";
        $num = 2;
        foreach ($data as $value) {
            if (isset($value['style'])) {
                $style = $value['style'];
                unset($value['style']);
            }

            foreach ($value as $column) {
                if (!isset($cAfter)) $cAfter = $cBegin;
                else $cAfter++;
                $office->setCellValue("{$cAfter}{$num}", "{$column}");
            }
            if (isset($style)) {
                $office->getStyle("{$cBegin}{$num}:{$cAfter}{$num}")->applyFromArray($this->set_row_style($style));
            }
            $num++;
            unset($cAfter, $value, $style);
        }

        return $spreadsheet;
    }

    /**
     * 保存本地导出
     * @param array $data
     * @param array $titles
     * @param string $title_style
     * @return string
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    function save_export(array $data, array $titles = [], string $title_style = ""): string
    {
        $write = new Xlsx($this->export($data, $titles, $title_style));
        $write->setPreCalculateFormulas(false); // 禁用计算公式功能，可以有效的提升写入表的性能

        $save_path = "uploads" . DS . "export" . DS . date('Ym');
        $save_file = date('Ymd') . '-' . generateUniqueCode('T') . '.xlsx';
        createDirs((BASE_DIR . $save_path));
        $write->save((BASE_DIR . $save_path . DS . $save_file));

        return API_DOMAIN . $save_path . DS . $save_file;
    }

    /**
     * 直接输出到浏览器自动下载
     * @param array $data
     * @param array $titles
     * @param string $title_style
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    function download_export(array $data, array $titles = [], string $title_style = "") : void
    {
        $spreadsheet = $this->export($data, $titles, $title_style);

        $save_file = date('Ymd') . '-' . generateUniqueCode('T') . '.xlsx';

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $save_file . '"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }


    /**
     * 上传文件及本地文件直接导入
     * @param $file
     * @param array $sheets
     * @return array
     * @throws CoolApiException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    function import($file, array $sheets = []) : array
    {
        if (is_array($file)) {
            if ($file['size'] > (10 * 1024 * 1024)) {
                throw new CoolApiException("文件大小超过最大限制！");
            }
            $file = $file['tmp_name'];
        }

        $file_type = IOFactory::identify($file);
        $file_fix = strtolower($file_type);
        if (!in_array($file_fix, ['xlsx', 'xls', 'cvs'])) {
            throw new CoolApiException("不支持的文件类型！");
        }
        $reader = IOFactory::createReader($file_type);
        $reader->setReadDataOnly(true);
        if (empty($sheets)) {
            $reader->setLoadAllSheets();
        }else {
            $reader->setLoadSheetsOnly($sheets);
        }
        $loader = $reader->load($file);

        $return_data = [];
        $sheet_names = $loader->getSheetNames();
        foreach ($sheet_names as $sheet) {
            $return_data[$sheet] = $loader->setActiveSheetIndexByName($sheet)->toArray(null, true, true, true);
        }

        return $return_data;
    }
}
