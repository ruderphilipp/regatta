[![Stories in Ready](https://badge.waffle.io/ruderphilipp/regatta.png?label=ready&title=Ready)](https://waffle.io/ruderphilipp/regatta)
# regatta
Software project to manage (non-water) regattas of my current rowing club.
It is currently used to do events like a just for fun triathlon (swimming, rowing on an [ergometer](https://en.wikipedia.org/wiki/Indoor_rower), running) or an indoor rowing event where the competitors have to run afterwards.

Why did we not use existing software on the market?

On the one hand, I am not aware of any good solution for the organization and realization of such (on land/ indoor) rowing events.
In my club we usualy got an consultant that is doing this kind of events on every weekend since many years and who has his own home-grown software.
However, this software is not for sale.

Secondly, we wanted to use [RFID](https://en.wikipedia.org/wiki/Radio-frequency_identification#Sports) wristbands for better time tracking.
We decided for wristbands since they are much cheeper than a timing mat and are good enough for our use case.
The RFID chips work like bar codes but are much easier to handle, both for the organizers and the competitors.
Organizers just have to hand out a wristband (similar to e.g. public swimming pools or libraries) and to register the ID in the system.
Competitors to not have to care about a piece of paper or remembering their number any more.
Instead they just hold their wristband against a sensor until it beeps, e.g. when passing a checkpoint or at the finishing line.
Of course, you have to have more than one sensor at the finishing line to guarantee fair results and so that other competitors can register in parallel.

## Technical details ##

### System requirements ###

The software is based on [Symfony 3](http://symfony.com/) and needs at least the following:

- [PHP](http://php.net/) 5.4 or higher
- a database supported by [Symfony](http://symfony.com/) - I currently use [MariaDB](https://en.wikipedia.org/wiki/MariaDB)
- [Python](https://www.python.org/) 3 to run the time tracking script

The software is designed to run "offline", meaning in a local area network without internet connection.

### Installation and start ###

After checking out the project, `cd` into the folder and run `composer.phar install` (see [Composer website](https://getcomposer.org/) for more information).

To start a local PHP server, do a `php bin/console server:start` from the root directory of this project.

We use multiple [RaspberryPi](https://www.raspberrypi.org/) with a small Linux installation to run the time tracking app.
These get one to many RFID readers connected via USB, depending on the checkpoint.

**Keep in mind to syncronize the clocks of the computers before starting races!**
This can be done by system software like NTP or by using the server as master and calling the `/api/timing/server` URL like so:

```bash
date --set=@`curl -s http://<server:port>/api/timing/server`
```
