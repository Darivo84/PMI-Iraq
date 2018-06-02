@extends('layout')
@section('content')
		<style>
				.form-add-user{
					margin:  0 auto;
					width: 800px;
				}
				.form-add-user form,.form-add-user table{
					width: 100%;
				}
		</style>
		<div class="form-add-user">
			{!! Form::open(array(
	            "autocomplete" => "off",
	            "id" => "form-add-user"
	        )) !!} 
	        	<table>
	        		<tr>
	        			<td class="label">First Name</td>
	        			<td>	        				
				            {!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        			<td class="label">Middle Name</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        		</tr>
	        		<tr>
	        			<td class="label">Last Name</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        			<td class="label">User Name</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        		</tr>
	        		<tr>
	        			<td class="label">Email</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        			<td class="label">Contact Number</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        		</tr>  
	        		<tr>
	        			<td class="label">Password</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        			<td class="label">Re-type Password</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        		</tr>        	
	        		<tr>
	        			<td class="label">Nationality</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        			<td class="label">Language</td>
	        			<td>
	        				{!! Form::text("name",Input::get("name"), array(
				                "class" => "input",
				                "style" => "width:250px"
				            )) !!} 
	        			</td>
	        		</tr>        			        		        		        		        		
	        	</table>
	        {!! Form::close() !!}
		</div>
@endsection
