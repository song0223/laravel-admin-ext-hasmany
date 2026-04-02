<div class="{{$viewClass['form-group']}} {!! !$errors->has($errorKey) ? '' : 'has-error' !!}">
    <label class="{{$viewClass['label']}} control-label">{{$label}}</label>
    <div class="{{$viewClass['field']}}">
        @include('admin::form.error')

        <div id="{{$table_id}}" class="json-table-field">
            <table class="table table-bordered">
                <thead>
                <tr>
                    @foreach($json_table_columns as $column => $definition)
                        <th>{{ $definition['label'] ?? $column }}</th>
                    @endforeach
                    <th style="width: 80px;">操作</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rows as $index => $row)
                    <tr>
                        @foreach($json_table_columns as $column => $definition)
                            <td>
                                @if(($definition['type'] ?? 'text') === 'textarea')
                                    <textarea name="{{$name}}[{{$index}}][{{$column}}]" class="form-control" rows="3">{{ $row[$column] ?? '' }}</textarea>
                                @else
                                    <input type="text" name="{{$name}}[{{$index}}][{{$column}}]" value="{{ $row[$column] ?? '' }}" class="form-control">
                                @endif
                            </td>
                        @endforeach
                        <td>
                            <button type="button" class="btn btn-danger btn-xs json-table-remove">删除</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <button type="button" class="btn btn-default btn-sm json-table-add">新增一行</button>

            <template>
                <tr>
                    @foreach($json_table_columns as $column => $definition)
                        <td>
                            @if(($definition['type'] ?? 'text') === 'textarea')
                                <textarea name="{{$name}}[__INDEX__][{{$column}}]" class="form-control" rows="3"></textarea>
                            @else
                                <input type="text" name="{{$name}}[__INDEX__][{{$column}}]" value="" class="form-control">
                            @endif
                        </td>
                    @endforeach
                    <td>
                        <button type="button" class="btn btn-danger btn-xs json-table-remove">删除</button>
                    </td>
                </tr>
            </template>
        </div>

        @include('admin::form.help-block')
    </div>
</div>
