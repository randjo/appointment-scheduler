<div>
	<button wire:click="openModal"
		class="{{$isEditMode ? 'hidden' : 'px-4 py-2 bg-green-500 text-white rounded w-100'}}"
	    id="appointment-wizard-button">
		@if(!$isEditMode)
			Create Appointment
		@endif
	</button>

	@if($showModal)
		<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
			<div class="absolute inset-0" wire:click="$set('showModal', false)"></div>
			<div class="relative z-10 w-full max-w-3xl rounded-lg bg-white p-6 shadow-lg">
				<button
						class="absolute right-3 top-3 text-gray-500"
						wire:click="$set('showModal', false)"
				>
					✕
				</button>

				@if($step === 1)
					<div>
						Select Date
						<select wire:model.live="selectedDate">
							<option value="">Select date</option>

							@foreach($dates as $day)
								<option value="{{ $day }}">{{ $day }}</option>
							@endforeach
						</select>
						@error('selectedDate')
						<small class="text-danger">{{ $message }}</small>
						@enderror

						@if(!empty($selectedDate))
							<button
									wire:click="goToStepTime"
									class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700"
							>
								Next
							</button>
						@endif
					</div>
				@endif

				@if($step === 2)
					<div>
						<strong>Date:</strong> {{ $selectedDate }}
					</div>
					<div>
						Select Start Time
						<select wire:model.live="selectedTime">
							<option value="">Select time</option>

							@foreach($times as $time)
								<option value="{{ $time }}">{{ $time }}</option>
							@endforeach
						</select>
						@error('selectedTime')
						<small class="text-danger">{{ $message }}</small>
						@enderror
					</div>
					<div>
						<button wire:click="$set('step', 1)"
						        class="px-4 py-2 rounded font-medium bg-blue-600 text-white hover:bg-blue-700">
							< Back
						</button>
						@if(!empty($selectedTime))
							<button
									wire:click="goToStepAppointmentData"
									class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700"
							>
								Next
							</button>
						@endif
					</div>
				@endif

				@if($step === 3)
					<div>
						<strong>Date:</strong> {{ $selectedDate }}
					</div>

					<div>
						<strong>Time:</strong> {{ $selectedTime }}
					</div>

					<div>
						<input type="hidden" name="client_mode" id="client_mode" value="{{ old('client_mode', 'existing') }}">
						<input type="hidden" name="appointment_at" id="appointment_at">

						<div class="btn-group mb-3">
							<button
									type="button"
									wire:click="setClientMode('existing')"
									class="px-3 py-2 rounded {{ $client_mode === 'existing' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-black' }}"
							>
								Existing Client
							</button>

							<button
									type="button"
									wire:click="setClientMode('new')"
									class="px-3 py-2 rounded {{ $client_mode === 'new' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-black' }}"
							>
								New Client
							</button>
						</div>
					</div>

					@if($client_mode === 'existing')
						<div class="mb-3">
							<label>Select Client</label>
							<select wire:model="client_id" name="client_id" class="form-control">
								<option value="">-- Select Client --</option>
								@foreach($clients as $client)
									<option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
										{{ $client->first_name }} {{ $client->last_name }} ({{ $client->egn }})
									</option>
								@endforeach
							</select>
							@error('client_id')
							<small class="text-danger">{{ $message }}</small>
							@enderror
						</div>
					@endif

					@if($client_mode === 'new')
						<div>
							<label class="form-label" for="first_name">First Name</label>
							<input wire:model="first_name" id="first_name" class="form-control" placeholder="First Name">
							@error('first_name')
							<small class="text-danger">{{ $message }}</small>
							@enderror
						</div>
						<div>
							<label class="form-label">Last Name</label>
							<input wire:model="last_name" class="form-control" placeholder="Last Name">
							@error('last_name')
							<small class="text-danger">{{ $message }}</small>
							@enderror
						</div>
						<div>
							<label class="form-label">EGN</label>
							<input wire:model="egn" class="form-control" placeholder="EGN">
							@error('egn')
							<small class="text-danger">{{ $message }}</small>
							@enderror
						</div>
					@endif

					<div class="mb-3">
						<label class="form-label">Description (optional)</label>
						<textarea wire:model="description" name="description" class="form-control"></textarea>
					</div>

					<div class="mb-3">
						<label class="form-label">Notification Type</label>
						<select wire:model="notification_type" class="form-control">
							@foreach(\App\Enums\NotificationType::cases() as $type)
								<option value="{{ $type->value }}" {{ old('notification_type') == $type->value ? 'selected' : '' }}>
									{{ ucfirst($type->value) }}
								</option>
							@endforeach
						</select>
						@error('notification_type')
						<small class="text-danger">{{ $message }}</small>
						@enderror
					</div>

					<div>
						<button wire:click="$set('step', 2)"
						        class="px-4 py-2 rounded font-medium bg-blue-600 text-white hover:bg-blue-700"
						>
							< Back
						</button>

						<button wire:click="save" class="btn btn-primary">
							{{ $isEditMode ? 'Update Appointment' : 'Create Appointment' }}
						</button>
					</div>
				@endif
			</div>
		</div>
	@endif
</div>

<script>
	@if($isEditMode)
		$(function () {
			$("#appointment-wizard-button").click();
		})
	@endif
</script>