<?php

namespace BookStack\Console\Commands;

use BookStack\Api\ApiToken;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateApiTokenCommand extends Command
{
    use HandlesSingleUser;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookstack:create-api-token
                            {--id= : Numeric ID of the user to create a token for}
                            {--email= : Email address of the user to create a token for}
                            {--name= : Name for the API token}
                            {--expires= : Expiry date for the token in Y-m-d format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API token for the given user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $user = $this->fetchProvidedUser();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $name = $this->option('name');
        if (empty($name)) {
            $name = $this->ask('Please specify a name for the API token');
        }

        $expires = $this->option('expires');

        $validator = Validator::make([
            'name'       => $name,
            'expires_at' => $expires,
        ], [
            'name'       => ['required', 'max:250'],
            'expires_at' => ['nullable', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $expiresAt = $validator->validated()['expires_at'] ?? ApiToken::defaultExpiry();
        $secret = Str::random(32);

        $token = (new ApiToken())->forceFill([
            'name'       => $validator->validated()['name'],
            'token_id'   => Str::random(32),
            'secret'     => Hash::make($secret),
            'user_id'    => $user->id,
            'expires_at' => $expiresAt,
        ]);

        while (ApiToken::query()->where('token_id', '=', $token->token_id)->exists()) {
            $token->token_id = Str::random(32);
        }

        $token->save();

        $this->info("API token created for user {$user->email}");
        $this->line('Token ID: ' . $token->token_id);
        $this->line('Secret: ' . $secret);
        $this->warn('The secret will not be shown again.');

        return self::SUCCESS;
    }
}
