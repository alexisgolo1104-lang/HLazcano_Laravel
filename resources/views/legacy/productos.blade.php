@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
<section class="card">
  <div class="card-title">Catálogo e inventario</div>
  <p>El registro, edición y consulta de existencias se administra desde el módulo de inventario.</p>
  <a class="btn btn-primary mt-8" href="{{ route('inventario') }}">Abrir inventario</a>
</section>
@endsection
