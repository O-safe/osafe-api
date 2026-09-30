<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the user model resource into an array representation for API responses.
     */
    public function toArray(Request $request): array
    {
        return [
            'userId'        => $this->user_id,
            'firstName'     => $this->first_name,
            'middleName'    => $this->middle_name,
            'lastName'      => $this->last_name,
            'emailAddress'  => $this->email,
            'mobileNumber'  => $this->mobile_number,
            'homeAddress'   => $this->home_address,
            'dateOfBirth'   => $this->date_of_birth,
            'nin'           => $this->nin,
            'lastLoginAt'   => $this->last_login_at
                ? Carbon::parse($this->last_login_at)->diffForHumans()
                : null,
            'createdAt'     => Carbon::parse($this->created_at)->toDayDateTimeString(),
            'updatedAt'     => Carbon::parse($this->updated_at)->toDayDateTimeString(),
            'createdBy'     => $this->created_by,
            'updatedBy'     => $this->updated_by,
            'title'         => [
                'titleId'   => $this->title_id ?? null,
                'titleName' => $this->relationLoaded('title')
                    ? $this->title?->title_name
                    : null,
            ],
            'gender'        => [
                'genderId'   => $this->gender_id ?? null,
                'genderName' => $this->relationLoaded('gender')
                    ? $this->gender?->gender_name
                    : null,
            ],
            'status'        => [
                'statusId'   => $this->status_id ?? null,
                'statusName' => $this->relationLoaded('status')
                    ? $this->status?->status_name
                    : null,
            ],
            'location'      => [
                'lgaId'       => $this->lga_id ?? null,
                'lgaName'     => $this->relationLoaded('lga')
                    ? $this->lga?->lga_name
                    : null,
                'stateId'     => $this->relationLoaded('lga')
                    ? $this->lga?->state_id
                    : null,
                'stateName'   => ($this->relationLoaded('lga') && $this->lga?->relationLoaded('state'))
                    ? $this->lga?->state?->state_name
                    : null,
                'countryId'   => ($this->relationLoaded('lga') && $this->lga?->relationLoaded('state'))
                    ? $this->lga?->state?->country_id
                    : null,
                'countryName' => ($this->relationLoaded('lga') && $this->lga?->relationLoaded('state') && $this->lga?->state?->relationLoaded('country'))
                    ? $this->lga?->state?->country?->country_name
                    : null,
            ],
            'passport'      => [
                'passportUrl' => $this->passport
                    ? Storage::url("passports/userPictures/{$this->passport}")
                    : null,
            ],
        ];
    }
}
