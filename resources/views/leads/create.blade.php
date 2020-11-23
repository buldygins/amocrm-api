@extends('layout')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Создание сделки') }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('leads.store') }}">
                            @csrf
                            <div class="row col-md-6">
                                <label for="name">Name</label><input id="name" type="text" class="form-control"
                                                                     name="name">
                            </div>

                            <div class="row col-md-6">
                                <label for="price">Price</label><input id="price" type="text" class="form-control"
                                                                       name="price">
                            </div>
                            <div class="row col-md-6">
                                <label>Company
                                    <input type="text" class="form-control" name="company">
                                </label>
                            </div>
                            <div class="row col-md-6">
                                <label>Responsible manager
                                    <select name="responsible_id">
                                        @foreach($responsible_users as $user)
                                            <option value="{{$user->getId()}}">{{$user->getName()}} </option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <div class="contacts"></div>
                            <div hidden>
                                <div class="contact">
                                    <h6>Contact</h6>
                                    <div class="row col-md-6">
                                        <label>Name
                                            <input id="name" type="text" class="form-control">
                                        </label>
                                    </div>
                                    <div class="row col-md-6">
                                        <label>Position
                                            <input id="position" type="text" class="form-control">
                                        </label>
                                    </div>
                                    <div class="row col-md-6">
                                        <label>Phone
                                            <input id="phone" type="text" class="form-control">
                                        </label>
                                    </div>
                                    <div class="row col-md-6">
                                        <label>Email
                                            <input id="email" type="text" class="form-control">
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="add">Add Contact</button>
                            <input type="hidden" id="contact_count" name="contact_count" value="0">
                            <div class="form-group row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Make a lead') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function () {
            var counter = 0;
            var name_form = $('div.contact');
            $('#add').click(function () {
                let form = name_form.clone();
                counter++;
                form.find('h6').html('Contact ' + counter);
                form.find("input#name").attr('name', 'contact' + counter + '_name');
                form.find("input#position").attr('name', 'contact' + counter + '_position');
                form.find("input#phone").attr('name', 'contact' + counter + '_phone');
                form.find("input#email").attr('name', 'contact' + counter + '_email');
                $('div.contacts').append(form);
                $('#contact_count').val(counter);
            });
        });
    </script>
@endsection
