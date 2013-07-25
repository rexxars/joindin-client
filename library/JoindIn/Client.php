<?php
/**
 * This file is part of the joind.in-client package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoindIn;

use Guzzle\Http\Client as HttpClient,
    Guzzle\Common\Collection,
    Guzzle\Service\Client as ServiceClient,
    Guzzle\Service\Description\ServiceDescription;

/**
 * PHP JoindIn client
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) 2013, Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/joind.in-client
 */
class Client extends ServiceClient {

    /**
     * API base URL
     *
     * @var string
     */
    const API_URL = 'http://api.joind.in/{version}';

    /**
     * Get an array of events
     *
     * @param  array $options Options for this command, see client.json
     * @return array Array of events
     */
    public function getEvents(array $options = array()) {
        $events = $this->runCommand(
            'events.get',
            array(),
            $options
        );

        return $this->assignIdsFromUri($events);
    }

    /**
     * Get information on a specific event
     *
     * @param  integer $eventId ID of the event to fetch
     * @param  array   $options Options for this command, see client.json
     * @return array
     */
    public function getEvent($eventId, array $options = array()) {
        return $this->runCommand(
            'event.get',
            array(
                'eventId' => $eventId,
                'verbose' => 'yes'
            ),
            $options
        );
    }

    /**
     * Get an array of comments for a given event
     *
     * @param  array $options Options for this command, see client.json
     * @return array Array of comments
     */
    public function getEventComments($eventId, array $options = array()) {
        return $this->runCommand(
            'event.comments.get',
            array('eventId' => $eventId),
            $options
        );
    }

    /**
     * Get an array of talks for a given event
     *
     * @param  array $options Options for this command, see client.json
     * @return array Array of talks
     */
    public function getEventTalks($eventId, array $options = array()) {
        $talks = $this->runCommand(
            'event.talks.get',
            array('eventId' => $eventId),
            $options
        );

        return $this->assignIdsFromUri($talks);
    }

    /**
     * Get an array of comments for a given talk
     *
     * @param  array $options Options for this command, see client.json
     * @return array Array of comments
     */
    public function getTalkComments($talkId, array $options = array()) {
        return $this->runCommand(
            'talk.comments.get',
            array('talkId' => $talkId),
            $options
        );
    }

    /**
     * Get information on a given talk
     *
     * @param  integer $talkId  ID of the talk to fetch
     * @param  array   $options Options for this command, see client.json
     * @return array
     */
    public function getTalk($talkId, array $options = array()) {
        return $this->runCommand(
            'talk.get',
            array(
                'talkId'  => $talkId,
                'verbose' => 'yes'
            ),
            $options
        );
    }

    /**
     * Get information on a given user
     *
     * @param  integer $userId  ID of the user to fetch
     * @param  array   $options Options for this command, see client.json
     * @return array
     */
    public function getUser($userId, array $options = array()) {
        return $this->runCommand(
            'user.get',
            array(
                'userId'  => $userId,
                'verbose' => 'yes'
            ),
            $options
        );
    }

    /**
     * Get an array of events a given user has attended
     *
     * @param  integer $userId  ID of the user to fetch attended events for
     * @param  array   $options Options for this command, see client.json
     * @return array Array of events
     */
    public function getEventsAttendedByUser($userId, array $options = array()) {
        return $this->runCommand(
            'events.attended.get',
            array('userId'  => $userId),
            $options
        );
    }

    /**
     * Get an array of talks given by a given user
     *
     * @param  integer $userId ID of the user to fetch talks for
     * @param  array   $options Options for this command, see client.json
     * @return array Array of talks
     */
    public function getTalksGivenByUser($userId, array $options = array()) {
        return $this->runCommand(
            'talks.byuser.get',
            array('userId'  => $userId),
            $options
        );
    }

    /**
     * Factory method to create a new client.
     *
     * @param  array|Collection $config Configuration data
     * @return Client
     */
    public static function factory($config = array()) {
        $defaults = array(
            'base_url' => self::API_URL,
            'version' => 'v2.1',
            'command.params' => array(
                'command.on_complete' => function($command) {
                    $response = $command->getResult();

                    // We don't need the meta blocks
                    unset($response['meta']);

                    // If we're down to a single element (events, talks etc)
                    // return only this element
                    if (count($response) == 1) {
                        $command->setResult(reset($response));
                    }
                }
            )
        );

        $required = array(
            'base_url',
            'version',
        );

        $config = Collection::fromConfig($config, $defaults, $required);

        $client = new self($config->get('base_url'), $config);

        // Attach a service description to the client
        $description = ServiceDescription::factory(__DIR__ . '/client.json');
        $client->setDescription($description);

        return $client;
    }

    /**
     * Run a command by the given name, merging default and passed options
     *
     * @param  string $command        Name of command to run
     * @param  array  $defaultOptions Default options for this command
     * @param  array  $options        User-specified options to merge
     * @return mixed
     */
    protected function runCommand($command, array $defaultOptions = array(), array $options = array()) {
        $command = $this->getCommand($command, array_merge(
            $defaultOptions,
            $options
        ));

        $command->execute();
        return $command->getResult();
    }

    /**
     * Loops through a set of entries, assigning them IDs based on the item URI
     *
     * @param  array $entries Entries to assign IDs for
     * @return array
     */
    protected function assignIdsFromUri($entries) {
        foreach ($entries as &$entry) {
            $entry['id'] = (int) preg_replace('#.*?(\d+)$#', '$1', $entry['uri']);
        }

        return $entries;
    }
}
