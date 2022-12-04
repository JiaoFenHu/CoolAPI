<?php
$this->subsetApi = ['name' => '报表导出导入通用'];
if (isset($this->module)) {
    $that = $this->module;
    //配置公用参数
    $this->docParams['token'] = ['type' => 'string', 'summary' => 'token'];
    $this->docParams['name'] = ['type' => 'string', 'summary' => '名称'];
    $this->docParams['file'] = ['type' => 'file', 'summary' => '文件'];
}

$this->info = ['req' => 'excel.export'];
$this->info['summary'] = 'excel导出';
if ($this->checkThisApi()) {
    $this->info['method'] = 'GET';
    $this->parameter = [];
    $this->fields = [];
    $param = $this->apiInit();
    //具体执行代码
    $office = $this->loadService($this ,"office");

    /**
     * 导出数据Excel文件到本地，默认导出文件类型：xlsx
     * 导出本地不会直接输出到浏览器，方法返回保存本地的绝对地址
     * 导出代码示例：
     * ```
     * $office->save_export($data, $title, 'green');
     * ```
     *
     * 参数1：数据组，二维数组数据，每个二维数组的元素个数要个title对应上，即：count($data[0]) === count($title)
     *  - 如果想针对单行设置样式，可在每个二维数组的末尾加上  style元素，目前提供四种样式：
     *  - success：绿色
     *  - warning：黄色
     *  - error：红色
     *  - disable：灰色
     *  - empty：无（默认）
     * 参数2：报表的标题设置，元素的个数要个"参数1"对应，可以针对单个标题设置单元格样式：
     *  - color：设置颜色（argb）
     *  - size：设置字体大小（integer）
     *  - bg_color：设置背景颜色（argb）
     *  - bold：设置字体是否加粗（boolean）
     *  - hPosition：水平方向（string：left，right，center）全局默认center
     *  - vPosition：垂直方向（string：top，bottom，center）全局默认center
     * 参数3：针对标题一整行的样式三个选项：green，blue，gray，不传递使用默认的样式，参数2会覆盖参数3的样式修改
     *
     * 如果需要设置每列单元格的宽度可参考以下示例：
     * ```
     * $office->set_column_width([10, 15, 10, 10, 40]);
     * ```
     * 参数是数组，数组的第一个元素的值代表的是表格的A，第二个代表B .... 以此类推。
     */
    $title = [
        '编号',
        '姓名',
        '年龄',
        '性别',
        '简介',
        /*'简介2' => [
            'color' => 'FFFF4500',
            'size' => 18,
            'bg_color' => 'FFFFD700'
        ]*/
    ];

    $data = [
        [1, '张三', 18, '男', '一个活泼的男孩子'],
        [2, '李四', 11, '男', '小男孩', 'style' => 'success'],
        [3, '李毅', 25, '女', '前端开发程序媛', 'style' => 'warning'],
        [4, '胡睿', 30, '男', '前端开发组长', 'style' => 'error'],
        [5, '孙刚', 25, '男', '社会我刚哥', 'style' => 'disable']
    ];

    try {
        $office->save_export($data, $title, 'green');
    }catch (Exception $e) {
        die($e->getMessage());
    }
    $data['success'] = true;
    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();


$this->info = ['req' => 'excel.export.download'];
$this->info['summary'] = 'excel直接下载到浏览器';
if ($this->checkthisapi()) {
    $this->info['method'] = 'GET';
    $this->parameter = [];
    $this->fields = [];
    $param = $this->apiinit();
    //具体执行代码
    $office = includeclass($this ,"office");

    /**
     * 将表格直接输出到浏览器，默认自动下载，此方法不建议使用ajax请求（ajax请求获得数据文件流），直接在标签做跳转操作
     * 该方法产生的报表不会保存到本地
     * 导出代码示例：
     * ```
     * $office->save_export($data, $title, 'green');
     * ```
     *
     * 参数1：数据组，二维数组数据，每个二维数组的元素个数要个title对应上，即：count($data[0]) === count($title)
     *  - 如果想针对单行设置样式，可在每个二维数组的末尾加上  style元素，目前提供四种样式：
     *  - success：绿色
     *  - warning：黄色
     *  - error：红色
     *  - disable：灰色
     *  - empty：无（默认）
     * 参数2：报表的标题设置，元素的个数要个"参数1"对应，可以针对单个标题设置单元格样式：
     *  - color：设置颜色（argb）
     *  - size：设置字体大小（integer）
     *  - bg_color：设置背景颜色（argb）
     *  - bold：设置字体是否加粗（boolean）
     *  - hPosition：水平方向（string：left，right，center）全局默认center
     *  - vPosition：垂直方向（string：top，bottom，center）全局默认center
     * 参数3：针对标题一整行的样式三个选项：green，blue，gray，不传递使用默认的样式，参数2会覆盖参数3的样式修改
     *
     * 如果需要设置每列单元格的宽度可参考以下示例：
     * ```
     * $office->set_column_width([10, 15, 10, 10, 40]);
     * ```
     * 参数是数组，数组的第一个元素的值代表的是表格的A，第二个代表B .... 以此类推。
     */
    $title = [
        '编号',
        '姓名',
        '年龄',
        '性别',
        '简介',
//        '简介2' => [
//            'color' => 'FFFF4500',
//            'size' => 18,
//            'bg_color' => 'FFFFD700'
//        ]
    ];

    $data = [
        [1, '张三', 18, '男', '一个活泼的男孩子'],
        [2, '李四', 11, '男', '小男孩', 'style' => 'success'],
        [3, '李毅', 25, '女', '前端开发程序媛', 'style' => 'warning'],
        [4, '胡睿', 30, '男', '前端开发组长', 'style' => 'error'],
        [5, '孙刚', 25, '男', '社会我刚哥', 'style' => 'disable']
    ];

    try {
        $office->set_column_width([10, 15, 10, 5, 30]);
        $office->download_export($data, $title, 'blue');
    }catch (Exception $e) {
        die($e->getMessage());
    }
    $data['success'] = true;
    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();


$this->info = ['req' => 'import'];
$this->info['summary'] = 'excel导入';
if ($this->checkthisapi()) {
    $this->info['method'] = 'POST';
    $this->parameter = ['file'];
    $this->fields = [];
    $param = $this->apiinit();
    //具体执行代码
    $office = includeclass($this ,"office");

    /**
     * 文件上传方式或本地文件导入，支持两种方式的读取excel表格;
     * 支持读取三种类型表格：xlsx，xls，cvs
     * 读取表格的多个sheet页数据组装返回;
     * 可以读取指定sheet页面(默认读取全部的sheet页)，需要传递第二个参数，下面示例:
     * ```
     * $office->import($_FILES['file'], ['sheet1', 'sheet2']);  // 上传形式读取表格数据
     * ```
     *
     * 本地文件读取，本地读取必须传递字符串绝对地址，相对地址可能出现未知问题
     * 注意：文件的权限
     * ```
     * $office->import(__DIR__ . '/test.xlsx');
     * ```
     */
    try {
        $data = $office->import($_FILES['file']);
        $data = $office->import(__DIR__ . '/test.xlsx');
    }catch (Exception $e) {
        exit($e->getMessage());
    }
    //输出返回数据
    $this->echodata($data);
}
//添加所有接口参数
$this->addsubset();