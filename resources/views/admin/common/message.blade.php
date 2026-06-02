@if ($errors->any())
    <div class="alert alert-danger alert-dismissible mt-4" >
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-ban"></i> Alert!</h5>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{$error}}</li>
            @endforeach
        </ul>
    </div>
@endif
@if(session()->has('message'))
    <div class="alert alert-success alert-dismissible mt-4">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> Alert!</h5>
        {{session('message')}}
    </div>
@endif
@if(session()->has('err_message'))
    <div class="alert alert-danger alert-dismissible mt-4">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-ban"></i> Alert!</h5>
        {{session('err_message')}}
    </div>
@endif
