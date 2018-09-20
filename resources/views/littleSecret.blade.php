<input name="secret" type="checkbox" title="神秘代码" {{$secret ? 'checked' : ''}}>神秘代码
<script src="/js/jquery.min.js"></script>
<script>
    $(document).ready(function () {
        $("input[name=secret]").on('change',function () {
            var val = $(this).is(":checked") ? 1 : 0;
            $.get("{{url()->current()}}?secret="+val,function (res) {
                if (res.status == 200){
                    alert('保存成功');
                }
            })
        })
    })
</script>