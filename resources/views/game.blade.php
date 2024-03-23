@extends('master-layout')

@section('title', 'Game')

@section('content')
<game-board-component :game-id="{{ json_encode($gameId) }}" :player-id="{{ json_encode($playerId) }}"></game-board-component>
@endsection
