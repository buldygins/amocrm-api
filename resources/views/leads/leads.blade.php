@extends('layout')
@section('content')
    <div class="container">
        @foreach($leads as $lead)
            ID:  {!! $lead->getId() !!} <br>
            Name:  {!! $lead->getName();!!} <br>
            Price:  {!! $lead->getPrice();!!}<br>
            Responsible User: {!! $responsible_user[$lead->getId()] !!} <br>
            @if(isset($entities[$lead->getId()]['contacts']))
                @foreach($entities[$lead->getId()]['contacts'] as $contact)
                    Contact:  {!!$contact->getName()!!}<br>
                    @if($contact->getCustomFieldsValues() !== null)
                        @foreach($contact->getCustomFieldsValues()->all() as $field)
                            {{$field->getFieldName()}}: {{$field->getValues()->first()->value}}<br>
                        @endforeach
                    @endif
                @endforeach
            @endif
            @if(isset($entities[$lead->getId()]['companies']))
                @foreach($entities[$lead->getId()]['companies'] as $company)
                    Company:   {!!$company->getName()!!}<br>
                    @if($company->getCustomFieldsValues() !== null)
                        @foreach($company->getCustomFieldsValues()->all() as $field)
                            {{$field->getFieldName()}}: {{$field->getValues()->first()->value}}<br>
                        @endforeach
                    @endif
                @endforeach
            @endif
            @if(!empty($notes[$lead->getId()]))
                Notes:
                @foreach($notes[$lead->getId()] as $note)
                  <br>{!! date('d/m/Y H:i ',$note->getCreatedAt()); if ($notes_username[$note->getId()] !== null){ echo $notes_username[$note->getId()];} !!}<br>
                    {!! $note->text !!}

                @endforeach
            @endif
            <hr>
        @endforeach
    </div>
@endsection

