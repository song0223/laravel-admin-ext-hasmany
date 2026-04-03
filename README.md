hasmany-extra
======

`laravel-admin` 扩展，提供两块能力：

- `hasMany` 里的多图上传、排序、删除
- `hasMany/NestedForm` 里的原生链式 `->when(...)` 条件显示
- `hasMany/NestedForm` 里的 `table(...)` JSON 表格兼容

## 安装

```bash
composer require bacao/laravel-admin-hasmany-extra
```

## 字段注册

扩展安装后会自动注册：

```php
$form->hasmanyExtraMultipleImage('banner', 'Banner');
```

如果你想保持旧项目里的方法名，也可以在项目自己的 `app/Admin/bootstrap.php` 里再加一行：

```php
\Encore\Admin\Form::extend('hasmanyMultipleImage', \Encore\HasmanyExtra\Fields\HasManyMultipleImage::class);
```

## 多图上传

多图上传的值解析、旧图排序同步、单张删除都已经内置：

```php
$form->hasMany('items', '商品项目', function (Form\NestedForm $form) {
    $form->hasmanyExtraMultipleImage('banner', 'Banner图')
        ->uniqueName()
        ->removable()
        ->sortable();
});
```

## 条件显示

安装这个扩展后，`hasMany` 里的 `radio()->when(...)` 可以直接按 laravel-admin 原生写法使用：

```php
$form->hasMany('items', '商品项目', function (Form\NestedForm $form) {
    $form->radio('type', '类型')->options([
        1 => '厨师介绍',
        2 => '餐品介绍',
        3 => '菜单介绍',
        4 => '酒店介绍',
    ])->when(3, function (Form\NestedForm $form) {
        $form->textarea('menu', '菜单');
    });
});
```

## hasMany 里的 table

在 `NestedForm` 里可以继续按接近 laravel-admin 原生的写法使用(目前只支持text和textarea)：

```php
$form->hasMany('items', '商品项目', function (Form\NestedForm $form) {
    $form->table('menu', '子菜单', function ($table) {
        $table->text('name', '名称');
        $table->textarea('desc', '描述');
    });
});
```

## 删除接口

扩展会自动注册后台路由：

```text
POST /admin/hasmany-extra/delete
```

用于处理 `hasMany` 子项里的单张旧图删除。
