## 表格类

> 表格类`use Kingbes\Libui\Table;`

  

#### 释放表格值

静态方法`freeValue`

*   参数
    *   `CData``$value`表格值
*   返回
    *   `void`

~~~php
public static function freeValue(CData $value): void
~~~

  

#### 获取表格值类型

静态方法`getValueType`

*   参数
    *   `CData``$v`表格值
*   返回
    *   `TableValueType`表格值类型

~~~php
public static function getValueType(CData $v): TableValueType
~~~

  

#### 创建字符串表格值

静态方法`createValueStr`

*   参数
    *   `string``$str`字符串
*   返回
    *   `CData`表格值

~~~php
public static function createValueStr(string $str): CData
~~~

  

#### 获取字符串表格值

静态方法`valueStr`

*   参数
    *   `CData``$v`表格值
*   返回
    *   `string`字符串

~~~php
public static function valueStr(CData $v): string
~~~

  

#### 创建图片表格值

静态方法`createValueImg`

*   参数
    *   `CData``$img`图片
*   返回
    *   `CData`表格值

~~~php
public static function createValueImg(CData $img): CData
~~~

  

#### 获取图片表格值

静态方法`valueImg`

*   参数
    *   `CData``$v`表格值
*   返回
    *   `CData`图片

~~~php
public static function valueImg(CData $v): CData
~~~

  

#### 创建整数表格值

静态方法`createValueInt`

*   参数
    *   `int``$i`整数
*   返回
    *   `CData`表格值

~~~php
public static function createValueInt(int $i): CData
~~~

  

#### 获取整数表格值

静态方法`valueInt`

*   参数
    *   `CData``$v`表格值
*   返回
    *   `int`整数

~~~php
public static function valueInt(CData $v): int
~~~

  

#### 创建颜色表格值

静态方法`createValueColor`

*   参数
    *   `float``$r`红色
    *   `float``$g`绿色
    *   `float``$b`蓝色
    *   `float``$a`透明度
*   返回
    *   `CData`表格值

~~~php
public static function createValueColor(float $r, float $g, float $b, float $a): CData
~~~

  

#### 获取颜色表格值

静态方法`valueColor`

*   参数
    *   `CData``$v`表格值
*   返回
    *   `array<float>`颜色

~~~php
public static function valueColor(CData $v): array
~~~

  

#### 获取模型处理函数

静态方法`modelHandler`

*   参数
    *   无
*   返回
    *   `CData`模型处理函数

~~~php
public static function modelHandler(): CData
~~~

  

#### 创建模型

静态方法`createModel`

*   参数
    *   `CData``$handler`模型处理函数
*   返回
    *   `CData`模型

~~~php
public static function createModel(CData $handler): CData
~~~

  

#### 模型行插入

静态方法`modelRowInserted`

*   参数
    *   `CData``$model`模型
    *   `int``$row`行
*   返回
    *   `void`

~~~php
public static function modelRowInserted(CData $model, int $row): void
~~~

  

#### 模型行改变

静态方法`modelRowChanged`

*   参数
    *   `CData``$model`模型
    *   `int``$row`行
*   返回
    *   `void`

~~~php
public static function modelRowChanged(CData $model, int $row): void
~~~

  

#### 模型行删除

静态方法`modelRowDeleted`

*   参数
    *   `CData``$model`模型
    *   `int``$row`行
*   返回
    *   `void`

~~~php
public static function modelRowDeleted(CData $model, int $row): void
~~~

  

#### 追加文本列

静态方法`appendTextColumn`

*   参数
    *   `CData``$model`模型
    *   `string``$name`名称
    *   `int``$textModelColumn`文本模型列
    *   `int``$textEditableModelColumn`文本可编辑模型列
    *   `CData``$textParams`文本参数
*   返回
    *   `void`

~~~php
public static function appendTextColumn(CData $model, string $name, int $textModelColumn, int $textEditableModelColumn, CData $textParams): void
~~~

  

#### 追加图片列

静态方法`appendImageColumn`

*   参数
    *   `CData``$model`模型
    *   `string``$name`名称
    *   `int``$imageModelColumn`图片模型列
*   返回
    *   `void`

~~~php
public static function appendImageColumn(CData $model, string $name, int $imageModelColumn): void
~~~

  

#### 追加图片文本列

静态方法`appendImageTextColumn`

*   参数
    *   `CData``$model`模型
    *   `string``$name`名称
    *   `int``$imageModelColumn`图片模型列
    *   `int``$textModelColumn`文本模型列
    *   `int``$textEditableModelColumn`文本可编辑模型列
    *   `CData``$textParams`文本参数
*   返回
    *   `void`

~~~php
public static function appendImageTextColumn(CData $model, string $name, int $imageModelColumn, int $textModelColumn, int $textEditableModelColumn, CData $textParams): void
~~~

  

#### 追加复选框列

静态方法`appendCheckboxColumn`

*   参数
    *   `CData``$model`模型
    *   `string``$name`名称
    *   `int``$checkboxModelColumn`复选框模型列
*   返回
    *   `void`

~~~php
public static function appendCheckboxColumn(CData $model, string $name, int $checkboxModelColumn): void
~~~

  

#### 追加复选框文本列

静态方法`appendCheckboxTextColumn`

*   参数
    *   `CData``$model`模型
    *   `string``$name`名称
    *   `int``$checkboxModelColumn`复选框模型列
    *   `int``$textModelColumn`文本模型列
    *   `int``$textEditableModelColumn`文本可编辑模型列
    *   `CData``$textParams`文本参数
*   返回
    *   `void`

~~~php
public static function appendCheckboxTextColumn(CData $model, string $name, int $checkboxModelColumn, int $textModelColumn, int $textEditableModelColumn, CData $textParams): void
~~~

  

#### 追加进度条列

静态方法`appendProgressBarColumn`

*   参数
    *   `CData``$model`模型
    *   `string``$name`名称
    *   `int``$progressBarModelColumn`进度条模型列
*   返回
    *   `void`

~~~php
public static function appendProgressBarColumn(CData $model, string $name, int $progressBarModelColumn): void
~~~

  

#### 追加按钮列

静态方法`appendButtonColumn`

*   参数
    *   `CData``$model`模型
    *   `string``$name`名称
    *   `int``$buttonModelColumn`按钮模型列
    *   `int``$buttonClickableModelColumn`按钮可点击模型列
*   返回
    *   `void`

~~~php
public static function appendButtonColumn(CData $model, string $name, int $buttonModelColumn, int $buttonClickableModelColumn): void
~~~

  

#### 创建表格

静态方法`create`

*   参数
    *   `CData``$params`参数
*   返回
    *   `CData`表格

~~~php
public static function create(CData $params): CData
~~~
