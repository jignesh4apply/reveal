<x-after-paywall-layout title="Finish Registration">
   @section('styles')
   <link rel="stylesheet" href="font/sf-pro/stylesheet.css">
   <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
   @endsection
   <section class="main-section">
      <div class="container">
         <div class="inner-section">
            <div class="main-header">
                <div class="inner-header age-header">
                    <div class="header-top">
                        <div>
                            <img class="logo" src="{{ asset('webAssets/images/svgs/logo.svg') }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="progress-top">
               <div class="progress-bar"></div>
               <div class="progress-step-group">
                  <div class="step step-checked">
                     <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.70227 2.13155C8.90567 2.33495 8.90567 2.66472 8.70227 2.86812L4.11894 7.45146C3.91554 7.65485 3.58576 7.65485 3.38237 7.45146L1.29903 5.36812C1.09563 5.16472 1.09563 4.83495 1.29903 4.63155C1.50243 4.42815 1.8322 4.42815 2.0356 4.63155L3.75065 6.3466L7.9657 2.13155C8.1691 1.92815 8.49887 1.92815 8.70227 2.13155Z" fill="white" />
                     </svg>
                     <span class="step-name">Paiement</span>
                  </div>
                  <div class="step">
                     <svg width="10" height="10" viewBox="0 0 11 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="5" cy="5" r="5" transform="matrix(1 0 0 -1 0.5 10)" fill="white" />
                     </svg>
                     <span class="step-name">Configuration</span>
                  </div>
                  <div class="step">
                     <svg width="10" height="10" viewBox="0 0 11 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="5" cy="5" r="5" transform="matrix(1 0 0 -1 0.5 10)" fill="white" />
                     </svg>
                     <span class="step-name">Téléchargement</span>
                  </div>
               </div>
            </div>
            <!-- Progress bar -->
            <div class="info-box">
               <div class="info-box-img">
                  <img src="{{ asset('webAssets/images/bg-16.png') }}">
               </div>
               <div class="info-box-content">
                  <h3 class="info-box-title">Vous faites maintenant partie de l'équipe !</h3>
                  <p class="info-box-description">Enregistrez votre adresse pour recevoir votre bracelet du club cette semaine.</p>
                  <img src="{{ asset('webAssets/images/bg-17.png') }}">
               </div>
            </div>
            <div class="question-title">
               <h3 class="title">Adresse</h3>
            </div>
            <form id="address-form" class="address-form" action="{{ url('address') }}" method="post">
               {{ csrf_field() }}
               <ul class="answers">
                  <li class="answer input-w-icon">
                     <input class="form-control" id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Entrez Prénom">
                     <label for="email">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M12 4C7.58128 4 4 7.58128 4 12C4 16.4187 7.58128 20 12 20C16.4187 20 20 16.4187 20 12C20 7.58128 16.4187 4 12 4ZM2 12C2 6.47672 6.47672 2 12 2C17.5233 2 22 6.47672 22 12C22 17.5233 17.5233 22 12 22C6.47672 22 2 17.5233 2 12ZM12.8839 8.36612C12.3957 7.87796 11.6043 7.87796 11.1161 8.36612C10.628 8.85427 10.628 9.64573 11.1161 10.1339C11.6043 10.622 12.3957 10.622 12.8839 10.1339C13.372 9.64573 13.372 8.85427 12.8839 8.36612ZM9.70191 6.9519C10.9711 5.6827 13.0289 5.6827 14.2981 6.9519C15.5673 8.22111 15.5673 10.2789 14.2981 11.5481C13.0289 12.8173 10.9711 12.8173 9.70191 11.5481C8.43271 10.2789 8.43271 8.22111 9.70191 6.9519ZM6.69142 16.3839C6.98757 14.5226 8.58812 13.088 10.544 13.088H13.456C15.4107 13.088 17.0124 14.5223 17.3086 16.3839C17.3954 16.9293 17.0236 17.4418 16.4781 17.5286C15.9327 17.6154 15.4202 17.2436 15.3334 16.6981C15.1876 15.7817 14.4013 15.088 13.456 15.088H10.544C9.59788 15.088 8.81243 15.7814 8.66658 16.6981C8.5798 17.2436 8.06729 17.6154 7.52187 17.5286C6.97645 17.4418 6.60464 16.9293 6.69142 16.3839Z" fill="#17191C" />
                        </svg>
                     </label>
                     @error('first_name')
                     <div class="invalid-feedback">{{ $message }}</div>
                     @enderror
                  </li>
                  <li class="answer input-w-icon">
                     <input class="form-control" id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Nom de famille">
                     <label for="email">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M12 4C7.58128 4 4 7.58128 4 12C4 16.4187 7.58128 20 12 20C16.4187 20 20 16.4187 20 12C20 7.58128 16.4187 4 12 4ZM2 12C2 6.47672 6.47672 2 12 2C17.5233 2 22 6.47672 22 12C22 17.5233 17.5233 22 12 22C6.47672 22 2 17.5233 2 12ZM12.8839 8.36612C12.3957 7.87796 11.6043 7.87796 11.1161 8.36612C10.628 8.85427 10.628 9.64573 11.1161 10.1339C11.6043 10.622 12.3957 10.622 12.8839 10.1339C13.372 9.64573 13.372 8.85427 12.8839 8.36612ZM9.70191 6.9519C10.9711 5.6827 13.0289 5.6827 14.2981 6.9519C15.5673 8.22111 15.5673 10.2789 14.2981 11.5481C13.0289 12.8173 10.9711 12.8173 9.70191 11.5481C8.43271 10.2789 8.43271 8.22111 9.70191 6.9519ZM6.69142 16.3839C6.98757 14.5226 8.58812 13.088 10.544 13.088H13.456C15.4107 13.088 17.0124 14.5223 17.3086 16.3839C17.3954 16.9293 17.0236 17.4418 16.4781 17.5286C15.9327 17.6154 15.4202 17.2436 15.3334 16.6981C15.1876 15.7817 14.4013 15.088 13.456 15.088H10.544C9.59788 15.088 8.81243 15.7814 8.66658 16.6981C8.5798 17.2436 8.06729 17.6154 7.52187 17.5286C6.97645 17.4418 6.60464 16.9293 6.69142 16.3839Z" fill="#17191C" />
                        </svg>
                     </label>
                     @error('last_name')
                     <div class="invalid-feedback">{{ $message }}</div>
                     @enderror
                  </li>
                  <li class="answer input-w-icon second-icon-input">
                     <select class="form-control form-select" id="country" name="country">
                        <option selected value="">Sélectionnez votre pays</option>
                     </select>
                     <label for="country">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M16.5695 6.72902C14.0459 4.20534 9.95415 4.20534 7.43046 6.72902C4.90677 9.25271 4.90677 13.3444 7.43046 15.8681L11.4637 19.9013C11.7599 20.1975 12.2401 20.1975 12.5363 19.9013L16.5695 15.8681C19.0932 13.3444 19.0932 9.25271 16.5695 6.72902ZM6.01624 5.31481C9.32098 2.01007 14.679 2.01007 17.9838 5.31481C21.2885 8.61955 21.2885 13.9776 17.9838 17.2823L13.9505 21.3155C12.8733 22.3928 11.1267 22.3928 10.0495 21.3155L6.01624 17.2823C2.71151 13.9776 2.71151 8.61955 6.01624 5.31481Z" fill="#17191C" />
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M16.5695 6.72902C14.0459 4.20534 9.95415 4.20534 7.43046 6.72902C4.90677 9.25271 4.90677 13.3444 7.43046 15.8681L11.4637 19.9013C11.7599 20.1975 12.2401 20.1975 12.5363 19.9013L16.5695 15.8681C19.0932 13.3444 19.0932 9.25271 16.5695 6.72902ZM6.01624 5.31481C9.32098 2.01007 14.679 2.01007 17.9838 5.31481C21.2885 8.61955 21.2885 13.9776 17.9838 17.2823L13.9505 21.3155C12.8733 22.3928 11.1267 22.3928 10.0495 21.3155L6.01624 17.2823C2.71151 13.9776 2.71151 8.61955 6.01624 5.31481Z" fill="#17191C" />
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M12.0018 9.73828C11.14 9.73828 10.4414 10.4369 10.4414 11.2987C10.4414 12.1605 11.14 12.8591 12.0018 12.8591C12.8636 12.8591 13.5623 12.1605 13.5623 11.2987C13.5623 10.4369 12.8636 9.73828 12.0018 9.73828ZM8.44141 11.2987C8.44141 9.33234 10.0355 7.73828 12.0018 7.73828C13.9682 7.73828 15.5623 9.33234 15.5623 11.2987C15.5623 13.2651 13.9682 14.8591 12.0018 14.8591C10.0355 14.8591 8.44141 13.2651 8.44141 11.2987Z" fill="#17191C" />
                        </svg>
                     </label>
                     @error('country')
                     <div class="invalid-feedback">{{ $message }}</div>
                     @enderror
                     <div class="second-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L12 12.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289Z" fill="#17191C"></path>
                        </svg>
                     </div>
                  </li>
                  <li class="answer input-w-icon second-icon-input">
                     <input class="form-control" id="city" type="text" name="city" value="{{ old('city') }}" placeholder="Ville">
                     <label for="city">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M3.99651 3.99658H14.0007C14.5532 3.99658 15.0011 4.44448 15.0011 4.997V20.0032H3.99651C3.444 20.0032 2.99609 19.5553 2.99609 19.0028V4.997C2.99609 4.44448 3.444 3.99658 3.99651 3.99658Z" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M15.0039 7.99854H20.006C20.5585 7.99854 21.0064 8.44644 21.0064 8.99895V19.0031C21.0064 19.5556 20.5585 20.0035 20.006 20.0035H15.0039V7.99854Z" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M7.00021 7.8732C6.93114 7.8732 6.87516 7.92919 6.87516 7.99826C6.87516 8.06732 6.93114 8.12331 7.00021 8.12331C7.06927 8.12331 7.12526 8.06732 7.12526 7.99826C7.12526 7.92919 7.06927 7.8732 7.00021 7.8732" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M11.0002 7.8732C10.9311 7.8732 10.8752 7.92919 10.8752 7.99826C10.8752 8.06732 10.9311 8.12331 11.0002 8.12331C11.0693 8.12331 11.1253 8.06732 11.1253 7.99826C11.1253 7.92919 11.0693 7.8732 11.0002 7.8732" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M7.00021 11.8752C6.93114 11.8752 6.87516 11.9311 6.87516 12.0002C6.87516 12.0693 6.93114 12.1253 7.00021 12.1253C7.06927 12.1253 7.12526 12.0693 7.12526 12.0002C7.12526 11.9311 7.06927 11.8752 7.00021 11.8752" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M11.0002 11.8752C10.9311 11.8752 10.8752 11.9311 10.8752 12.0002C10.8752 12.0693 10.9311 12.1253 11.0002 12.1253C11.0693 12.1253 11.1253 12.0693 11.1253 12.0002C11.1253 11.9311 11.0693 11.8752 11.0002 11.8752" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M7.00021 15.8766C6.93114 15.8766 6.87516 15.9326 6.87516 16.0017C6.87516 16.0707 6.93114 16.1267 7.00021 16.1267C7.06927 16.1267 7.12526 16.0707 7.12526 16.0017C7.12526 15.9326 7.06927 15.8766 7.00021 15.8766" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M11.0002 15.8766C10.9311 15.8766 10.8752 15.9326 10.8752 16.0017C10.8752 16.0707 10.9311 16.1267 11.0002 16.1267C11.0693 16.1267 11.1253 16.0707 11.1253 16.0017C11.1253 15.9326 11.0693 15.8766 11.0002 15.8766" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M18.0041 11.8752C17.9351 11.8752 17.8791 11.9311 17.8791 12.0002C17.8791 12.0693 17.9351 12.1253 18.0041 12.1253C18.0732 12.1253 18.1292 12.0693 18.1292 12.0002C18.1292 11.9311 18.0732 11.8752 18.0041 11.8752" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M18.0041 15.8766C17.9351 15.8766 17.8791 15.9326 17.8791 16.0017C17.8791 16.0707 17.9351 16.1267 18.0041 16.1267C18.0732 16.1267 18.1292 16.0707 18.1292 16.0017C18.1292 15.9326 18.0732 15.8766 18.0041 15.8766" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                     </label>
                     @error('city')
                     <div class="invalid-feedback">{{ $message }}</div>
                     @enderror
                  </li>
                  <li class="answer input-w-icon second-icon-input">
                     <input class="form-control " id="address" type="text" name="address" value="{{ old('address') }}" placeholder="Adresse">
                     <label for="address">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path d="M8 17.5024L10.0008 19.5033L8 21.5041" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M9.99859 19.5031H3.99609" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M14.0028 20.2743C17.744 19.4225 20.5344 16.2934 20.9538 12.4794C21.3732 8.66547 19.3295 5.00473 15.8628 3.3603C12.3961 1.71587 8.26809 2.44908 5.57974 5.18678C2.89138 7.92448 2.23336 12.0651 3.94056 15.5013" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M11 15.0014H15.0547C15.3309 15.0014 15.5549 14.7774 15.5549 14.5011V9.354" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M8.50021 9.354V13.0005" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M7.5 9.9993L12.0939 6.99805L16.5037 9.9993" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                     </label>
                     @error('address')
                     <div class="invalid-feedback">{{ $message }}</div>
                     @enderror
                     <div class="second-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M15.0067 7.5453C12.9463 5.4849 9.60571 5.4849 7.5453 7.5453C5.4849 9.60571 5.4849 12.9463 7.5453 15.0067C9.5831 17.0445 12.8731 17.0668 14.9384 15.0738C14.9586 15.0494 14.9801 15.0257 15.0029 15.0029C15.0257 14.9801 15.0494 14.9586 15.0738 14.9384C17.0668 12.8731 17.0445 9.5831 15.0067 7.5453ZM17.0798 15.6656C19.2421 12.8142 19.0225 8.73265 16.4209 6.13109C13.5794 3.28964 8.97255 3.28964 6.13109 6.13109C3.28964 8.97255 3.28964 13.5794 6.13109 16.4209C8.73265 19.0225 12.8142 19.2421 15.6656 17.0798L18.2929 19.7071C18.6834 20.0976 19.3166 20.0976 19.7071 19.7071C20.0976 19.3166 20.0976 18.6834 19.7071 18.2929L17.0798 15.6656Z" fill="#17191C" />
                        </svg>
                     </div>
                  </li>
                  <li class="answer input-w-icon">
                     <input class="form-control" id="postal_code" type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="Code postal">
                     <label for="postal-code">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path d="M10 18V22H14V18" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M6 12H7" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path fill-rule="evenodd" clip-rule="evenodd" d="M17.5 8H6.5C4.567 8 3 9.567 3 11.5V17C3 17.552 3.448 18 4 18H20C20.552 18 21 17.552 21 17V11.5C21 9.567 19.433 8 17.5 8Z" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M14 5V12" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <rect x="14" y="2" width="5" height="3" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M13.999 11.625C13.792 11.626 13.625 11.794 13.625 12.001C13.625 12.208 13.793 12.376 14 12.375C14.207 12.375 14.375 12.207 14.375 12C14.375 11.793 14.207 11.625 13.999 11.625" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M6.5 8C8.433 8 10 9.567 10 11.5V18" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                     </label>
                     @error('postal_code')
                     <div class="invalid-feedback">{{ $message }}</div>
                     @enderror
                  </li>
                  <li class="answer input-w-icon">
                     <input class="form-control" id="company" type="text" name="company" placeholder="Entreprise (facultatif)">
                     <label for="company">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path d="M12 6V8" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M9.5 11V9C9.5 8.44772 9.94772 8 10.5 8H13.5C14.0523 8 14.5 8.44772 14.5 9V11" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M7 21V12C7 11.4477 7.44772 11 8 11H16" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M9.5 14H13" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M9.5 17H11" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M12 21H7C4.79086 21 3 19.2091 3 17V7C3 4.79086 4.79086 3 7 3H17C19.2091 3 21 4.79086 21 7V11" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M22 17.5C22 19.1385 20.212 20.8888 19.192 21.7544C18.7892 22.0819 18.2118 22.0819 17.809 21.7544C16.788 20.8888 15 19.1385 15 17.5" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M22 17.5V17.5C22 15.567 20.433 14 18.5 14V14C16.567 14 15 15.567 15 17.5V17.5" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                           <path d="M18.499 17.24C18.3555 17.2406 18.2396 17.3572 18.24 17.5007C18.2404 17.6441 18.3569 17.7602 18.5003 17.76C18.6438 17.7598 18.76 17.6435 18.76 17.5C18.7604 17.4308 18.733 17.3643 18.6839 17.3154C18.6348 17.2665 18.5682 17.2394 18.499 17.24" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                     </label>
                  </li>
                  <li class="answer input-w-icon">
                     <input class="form-control" id="phone" type="text" name="Phone" placeholder="Téléphone (facultatif)">
                     <label for="phone">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                           <path d="M12.9995 6.99087C14.0305 6.97687 15.0665 7.35887 15.8535 8.14587C16.6405 8.93287 17.0225 9.96887 17.0085 10.9999M12.9995 2.96387C15.0555 2.96387 17.1125 3.74887 18.6815 5.31787C20.2505 6.88687 21.0355 8.94387 21.0355 10.9999M18.9645 16.6099L17.7915 15.4369C17.2055 14.8509 16.2555 14.8509 15.6705 15.4369L14.7485 16.3589C14.5425 16.5649 14.2305 16.6339 13.9645 16.5169C12.6285 15.9319 11.3085 15.0449 10.1315 13.8679C8.95954 12.6959 8.07554 11.3819 7.48954 10.0509C7.36754 9.77587 7.43854 9.45287 7.65154 9.23987L8.47754 8.41387C9.14854 7.74287 9.14854 6.79387 8.56254 6.20787L7.38954 5.03487C6.60854 4.25387 5.34254 4.25387 4.56154 5.03487L3.90954 5.68587C3.16854 6.42687 2.85954 7.49587 3.05954 8.55587C3.55354 11.1689 5.07154 14.0299 7.52054 16.4789C9.96954 18.9279 12.8305 20.4459 15.4435 20.9399C16.5035 21.1399 17.5725 20.8309 18.3135 20.0899L18.9645 19.4389C19.7455 18.6579 19.7455 17.3919 18.9645 16.6099Z" stroke="#17191C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                     </label>
                  </li>
               </ul>
               <button type="submit" class="form-submit">Continuer</button>
            </form>
         </div>
      </div>
   </section>
   @section('scripts')

   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
   <script>
      $(document).ready(function() {
         $('#country').select2({
            placeholder: "Sélectionnez votre pays",
            allowClear: true,
            width: '100%'
         });

         $.ajax({
            // url: 'https://restcountries.com/v3.1/all',
            url: '/countries',
            method: 'GET',
            success: function(data) {
               if (data && Array.isArray(data)) {
                  data.sort(function(a, b) {
                     return a.name.common.localeCompare(b.name.common);
                  });
                  data.forEach(function(country) {
                     var option = new Option(country.name.common, country.name.common, false, false);
                     $('#country').append(option);
                  });
                  $('#country').select2();
               } else {
                  alert('Invalid country data format.');
               }
            },
            error: function() {
               alert('Failed to load country data.');
            }
         });
      });
   </script>

   <script>
      $(document).ready(function() {
         $(function() {
            $('#address-form').validate({
               rules: {
                  name: {
                     required: true,
                  },
                  country: {
                     required: true
                  },
                  city: {
                     required: true
                  },
                  address: {
                     required: true
                  },
                  postal_code: {
                     required: true
                  }
               },
               messages: {
                  name: {
                     required: "Please enter your name",
                  },
                  country: {
                     required: "Please select country",
                  },
                  city: {
                     required: "Please enter your city"
                  },
                  address: {
                     required: "Please enter your address"
                  },
                  postal_code: {
                     required: "Please enter your postal code"
                  }
               },
               errorElement: 'div',
               errorPlacement: function(error, element) {
                  error.addClass('invalid-feedback');
                  element.closest('.answer').append(error);
               },
               highlight: function(element, errorClass, validClass) {
                  $(element).addClass('is-invalid');
               },
               unhighlight: function(element, errorClass, validClass) {
                  $(element).removeClass('is-invalid');
               }
            });
         });
      });
   </script>
   <script>
      toastr.options = {
         closeButton: true,
         progressBar: true,
         positionClass: 'toast-top-right',
         showMethod: 'slideDown',
         timeOut: 40000
      };

      @if(Session::has('success'))
      toastr.success("{{ session('success') }}", 'Success', {
         "background-color": "#4CAF50"
      });
      @endif

      @if(Session::has('error'))
      toastr.error("{{ session('error') }}", 'Error', {
         "background-color": "black"
      });
      @endif
   </script>
       <script src="https://cdn.amplitude.com/script/58b25352c5f050c3039f226c757dc9ba.js"></script>
       <script>window.amplitude.init('58b25352c5f050c3039f226c757dc9ba', {"fetchRemoteConfig":true,"autocapture":true});</script>
       <script>
           amplitude.track('TAdresse');
       </script>
   @endsection
</x-after-paywall-layout>
