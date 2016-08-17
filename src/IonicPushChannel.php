<?php

namespace NotificationChannels\IonicPushNotifications;

use GuzzleHttp\Client;
use NotificationChannels\IonicPushNotifications\Exceptions\CouldNotSendNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\IonicPushNotifications\Exceptions\InvalidConfiguration;

class IonicPushChannel
{
    const API_ENDPOINT = 'https://api.ionic.io/push/notifications';

    /** @var Client */
    protected $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @throws \NotificationChannels\IonicPushNotifications\Exceptions\InvalidConfiguration
     * @throws \NotificationChannels\IonicPushNotifications\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $routing = collect($notifiable->routeNotificationFor('IonicPush'))) {
            return;
        }

        $key = config('services.ionicpush.key');

        if (is_null($key)) {
            throw InvalidConfiguration::configurationNotSet();
        }

        $ionicPushData = array_merge($notification->toIonicPush($notifiable)->toArray(), ['tokens' => $routing->first()]);

        $response = $this->client->post(self::API_ENDPOINT, [
            'body' => json_encode($ionicPushData),
            'headers' => [
                'Authorization' => 'Bearer '.$key,
                'Content-Type' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() !== 201) {
            throw CouldNotSendNotification::serviceRespondedWithAnError($response);
        }
    }
}