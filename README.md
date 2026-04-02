hasmany-extra
======

`laravel-admin` 扩展，提供两块能力：

- `hasMany` 里的多图上传、排序、删除
- `hasMany/NestedForm` 里的原生链式 `->when(...)` 条件显示
- 以及手动辅助类 `NestedFormWhen`

## 安装

```bash
composer require laravel-admin-ext/hasmany-extra
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

## 排序同步

控制器里引入 trait：

```php
use Encore\HasmanyExtra\Concerns\HandlesHasManyMultipleImage;
```

在 `saved` 事件里同步：

```php
$form->saved(function (Form $form) {
    $this->syncHasManyMultipleImageOrder('items', GoodsItem::class, 'goods_id', $form->model()->id);
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

如果你想手动控制，也可以继续用辅助类：

```php
use Encore\HasmanyExtra\NestedFormWhen;

$type = $form->radio('type', '类型')->options([
    1 => '厨师介绍',
    2 => '餐品介绍',
    3 => '菜单介绍',
    4 => '酒店介绍',
]);

NestedFormWhen::make($form, $type)
    ->when(3, function (Form\NestedForm $form) {
        $form->textarea('menu', '菜单');
    });
```

## 删除接口

扩展会自动注册后台路由：

```text
POST /admin/hasmany-extra/delete
```

用于处理 `hasMany` 子项里的单张旧图删除。
