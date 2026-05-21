<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, CanResetPassword;

    protected $table = 'users';
    protected $dates = [
            'last_login'
        ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
     protected $fillable = [
            'password', 'first_name', 'last_name',
            'email', 'address', 'country_id',
            'username', 'country_admin_id', 'agent_id',
            'currency', 'sub_agent_id',
            'country_code',
            'avatar',
            'balance',
            'diposit_bonus',
            'reffer_bonus',
            'refunds_bonus',
            'total_balance',
            'shop_limit',
            'last_login',
            'confirmation_token',
            'status',
            'is_demo_agent',
            'google2fa_secret',
            'google2fa_enable',
            'rating',
            'agreed',
            'free_demo',
            'count_tournaments',
            'count_happyhours',
            'count_refunds',
            'count_progress',
            'count_daily_entries',
            'count_invite',
            'tournaments',
            'happyhours',
            'refunds',
            'progress',
            'daily_entries',
            'invite',
            'welcomebonus',
            'count_welcomebonus',
            'smsbonus',
            'count_smsbonus',
            'wheelfortune',
            'count_wheelfortune',
            'total_in',
            'total_out',
            'language',
            'phone',
            'phone_verified',
            'otp',
            'expire_otp',
            'sms_token',
            'inviter_id',
            'reffer_id',
            'remember_token',
            'role_id',
            'count_balance',
            'parent_id',
            'referral_code',
            'shop_id',
            'session',
            'is_blocked',
            'auth_token',
            'last_online',
            'created_at',
            'sms_token_date',
            'last_daily_entry',
            'last_bid',
            'last_progress',
            'last_wheelfortune',
            'evo_user_id',
            'game_session',
            'vip'
        ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function role(){
        return $this->belongsTo(Role::class,'role_id');
    }

    public function roles(){
        return $this->belongsToMany(Role::class,'role_user','user_id', 'role_id');
    }

    public function hasRole($role)
    {
        return $this->roles()->where('slug', $role)->exists();
    }
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class,'permission_user','user_id','permission_id');
    }

    public function hasPermission($slug)
    {
        return $this->permissions()->where('slug', $slug)->exists();
    }

    public function country(){
        return $this->belongsTo(Country::class,'country_id');
    }

    public function getFullNameAttribute(){
        return $this->first_name.' '.$this->last_name;
    }

    public function country_admin(){
        return $this->belongsTo(User::class,'country_admin_id');
    }
    public function refferAgent() {
        return $this->belongsTo(User::class,'reffer_id');
    }
    
    public function refferUsers() {
        return $this->hasMany(User::class,'reffer_id');
    }
    
    public function threeLevelRefferUsernames($lavel=null){
        // level 1
        $level1 = $this->refferUsers()->pluck('username');
        if($lavel==1){
          return $level1->values()->toArray();
        }
        // level 2
        $level2 = User::whereIn('reffer_id', $level1)->pluck('username');
        if($lavel==2){
          return $level2->values()->toArray();
        }
        // level 3
        $level3 = User::whereIn('reffer_id', $level2)->pluck('username');
        if($lavel==3){
          return $level3->values()->toArray();
        }
        // level 4
        // $level4 = User::whereIn('reffer_id', $level3)->pluck('username');

        // সবগুলো merge করে unique করে দিব
        return $level1
            ->merge($level2)
            ->merge($level3)
            // ->merge($level4)
            ->unique()
            ->values()
            ->toArray();
    }
    
    public function threeLevelRefferIds($lavel=null){
        // level 1
        $level1 = $this->refferUsers()->pluck('id');
        if($lavel==1){
          return $level1->values()->toArray();
        }
        // level 2
        $level2 = User::whereIn('reffer_id', $level1)->pluck('id');
        if($lavel==2){
          return $level2->values()->toArray();
        }
        // level 3
        $level3 = User::whereIn('reffer_id', $level2)->pluck('id');
        if($lavel==3){
          return $level3->values()->toArray();
        }
        // level 4
        //$level4 = User::whereIn('reffer_id', $level3)->pluck('id');
        return $level1
            ->merge($level2)
            ->merge($level3)
            // ->merge($level4)
            ->unique()
            ->values()
            ->toArray();
    }
    
    public function allReferralIds($from = null, $to = null){
        $all = collect();
        foreach ($this->refferUsers as $ref) {
            if (
                ($from === null || $ref->created_at->greaterThanOrEqualTo($from->copy()->startOfDay())) &&
                ($to === null || $ref->created_at->lessThanOrEqualTo($to->copy()->endOfDay()))
            ) {
                $all->push($ref->id);
            }
            $all = $all->merge($ref->allReferralIds($from, $to));
        }
        return $all;
    }
    
    public function getRefferLevel($childId)
    {
        // Level 1
        if ($this->refferUsers()->where('id', $childId)->exists()) {
            return 1;
        }
    
        // Level 2
        $level1 = $this->refferUsers()->pluck('id');
        if (User::whereIn('reffer_id', $level1)->where('id', $childId)->exists()) {
            return 2;
        }
    
        // Level 3
        $level2 = User::whereIn('reffer_id', $level1)->pluck('id');
        if (User::whereIn('reffer_id', $level2)->where('id', $childId)->exists()) {
            return 3;
        }
    
        // Level 4
        $level3 = User::whereIn('reffer_id', $level2)->pluck('id');
        if (User::whereIn('reffer_id', $level3)->where('id', $childId)->exists()) {
            return 4;
        }
    
        return null;
    }
    
    public function getVipRootUser(){
        $user = $this;
        $limit = 0;
        while ($user && $user->refferAgent && $limit < 10) {
            $user = $user->refferAgent;
            $limit++;
            if ($user->vip == true) {
                return $user;
            }
        }
        return null;
    }

    public function agent(){
        return $this->belongsTo(User::class,'agent_id');
    }
    
    public function photo(){
        return 'admin/assets/images/users/avatar-1.jpg';
    }
    
    public function countryFlag(){
        if($this->country_code=='IN'){
        return 'admin/assets/images/flags/in_flag.jpg';
        }elseif($this->country_code=='PK'){
        return 'admin/assets/images/flags/pk_flag.jpg';
        }else{
        return 'admin/assets/images/flags/bd_flag.jpg';
        }
    }
    
    public function countryName(){
        if($this->country_code=='IN'){
        return 'indian';
        }elseif($this->country_code=='PK'){
        return 'pakistan';
        }else{
        return 'bangladesh';
        }
    }
    
    public function countryCode(){
        if($this->country_code=='IN'){
        return 'IN (+91)';
        }elseif($this->country_code=='PK'){
        return 'PK(+92)';
        }else{
        return 'BD(+88)';
        }
    }
    
    public function countryCurrency(){
        if($this->country_code=='IN'){
        return 'INR';
        }elseif($this->country_code=='PK'){
        return 'PKR';
        }else{
        return 'BDT';
        }
    }
    
    public function countryPermit(){
        if($this->id=='IN'){
            //user wise is diffrent need permit
        return $this->country_code;
        }else{
        return $this->country_code;
        }
    }
    
    public function currencySymbol(){
        return match ($this->country_code) {
            'IN' => '₹',
            'PK' => '₨',
            default => '৳',
        };
    }
    
    public function permitCountryFlag(){
        if($this->countryPermit()=='IN'){
        return 'admin/assets/images/flags/in_flag.jpg';
        }elseif($this->countryPermit()=='PK'){
        return 'admin/assets/images/flags/pk_flag.jpg';
        }else{
        return 'admin/assets/images/flags/bd_flag.jpg';
        }
    }
    
    public function deposits() {
        return $this->hasMany(Deposit::class);
    }
    
    public function adminDepostsDeductions() {
        return $this->hasMany(ManualDeposit::class,'user_id');
    }
    
    public function adminDeposts() {
        return $this->hasMany(ManualDeposit::class,'user_id')->where('type',0);
    }
    
    public function balanceDeposts() {
        return $this->hasMany(BalanceDeposit::class,'user_id');
    }
    
    public function adminDeductions() {
        return $this->hasMany(ManualDeposit::class,'user_id')->where('type',1);
    }

    public function withdraws() {
        return $this->hasMany(Withdraw::class);
    }

    public function isFirstDeposit(){
        $status=true;
        $hasSuccess =$this->deposits()->where('status',1)->first();
        if($hasSuccess){
            $status=false;
        }
        return $status;
    }
    
    public function bonuses() {
        return $this->hasMany(UserBonus::class);
    }
    
    public function wallets() {
        return $this->hasMany(UserWallet::class, 'user_id');
    }
    
    public function transfers() {
        return $this->hasMany(Transfer::class, 'user_id');
    }
    
    public function buyTickets() {
        return $this->hasMany(LotteryTicket::class, 'user_id');
    }
    
    public function casinoHistory(){
        return $this->hasMany(WalletTransaction::class,'player_id','username');
    }
    
    public function ingHistory(){
        return $this->hasMany(Inggamehistory::class,'user_id');
    }
    
    public function sportHistory(){
        return $this->hasMany(PronetWalletTransactions::class,'user_id');
    }
    
    public function wingoHistory(){
        return $this->hasMany(WingoHistory::class,'user_id');
    }
    
    public function commissionhistories()
    {
        return $this->hasMany(Commissionhistory::class,'user_id');
    }
    
    
}
