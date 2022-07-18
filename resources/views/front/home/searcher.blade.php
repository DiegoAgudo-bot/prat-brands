<div class="row d-flex justify-content-center">
    <div>
        <div class="col-md-12">
            <div id="ajax_message" class="alert alert-danger d-none">
            </div>
            @if (Session::has('retCode'))
                @if (Session::get('retCode') == 1)
                    <div class="alert alert-danger">
                        {{ Session::get('msj') }}
                    </div>
                @else
                    <div class="alert alert-success">
                        {{ Session::get('msj') }}
                    </div>
                @endif
            @endif
            <div class="card p-4 mt-3">
                <h3 class="heading mt-5 text-center">TEMPERATURE CHECKER</h3>
                <select id="select2" class="form-select">
                    <option value="">Search city...</option>
                    @foreach($allCities as $city)
                        <option value="{{$city['id']}}">{{$city['value']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="loadTemperature"></div>
    </div>
</div>

@push('styles')
    <link href="{{ asset('css/search.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        //Initialize select2 so only the searched options will be displayed
        $('#select2').select2({
            placeholder: "Select city...",
            minimumInputLength: 2,
        });

        //Listener to input changes
        $('#select2').on('change', function(e) {
            let value = $('#select2').select2('val');

            $('#loadTemperature').empty();
            
            $.ajax({
                url: '{{route("getTemperature")}}',
                data: {
                    "_token": "{{ csrf_token() }}",
                    "city_id": value,
                },
                type: "post",
                cache: false,
                success: function(predictions) {
                    //We get all the data from the selected city and build the html to insert it to the DOM
                    $('#ajax_message').addClass('d-none');
                    var counter = 0;
                    Object.keys(predictions).forEach(key => {
                        var string = `
                            <div class="col-md-8 mx-auto mt-3">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="">
                                            <h4 data-bs-toggle="collapse" data-bs-target="#div_` + counter + `" aria-expanded="false" aria-controls="div_` + counter + `">` + key + `<img width="24px" height="24px" src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d8/Font_Awesome_5_solid_arrow-circle-down.svg/512px-Font_Awesome_5_solid_arrow-circle-down.svg.png?20180810202539"></h4>
                                        </div>
                                    </div>
                                    <div class="card-body collapse" id="div_` + counter + `">
                                        <div class="container"> 
                                            <div class="row"> 
                                                <div class="col"> 
                                                    <blockquote class="blockquote mb-0">`;
                                        //For all the collapsing part, we need to loop again all the options, and delete the first one so it doesnt repeat
                                        Object.keys(predictions[key]).forEach(prediction => {
                                            string += `<p><h4>` + prediction + `<img data-bs-toggle="collapse" data-bs-target="#` + prediction + `_` + key + `" aria-expanded="false" aria-controls="` + prediction + `_` + key + `" width="24px" height="24px" src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d8/Font_Awesome_5_solid_arrow-circle-down.svg/512px-Font_Awesome_5_solid_arrow-circle-down.svg.png?20180810202539"></h4>` + Object.keys(predictions[key][prediction])[0] + ` | ` + predictions[key][prediction][Object.keys(predictions[key][prediction])[0]]  + `</p>
                                                        <div class="collapse" id="` + prediction + `_` + key + `">`;
                                                        delete predictions[key][prediction][Object.keys(predictions[key][prediction])[0]];
                                                        Object.keys(predictions[key][prediction]).forEach(hour => {
                                                            string += `<p>` + hour + ` | ` + predictions[key][prediction][hour] + `</p>`;
                                                        });

                                            string += `</div>`;
                                        });

                                        string += `</blockquote>
                                                </div>
                                            </div> 
                                        </div> 
                                    </div> 
                                </div>
                            </div>
                        `;
                        $('#loadTemperature').append(string);
                        counter++;
                    });
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    $('#ajax_message').html(xhr.responseJSON.message).removeClass('d-none');
                }
            });
        });
    })
</script>
@endpush

