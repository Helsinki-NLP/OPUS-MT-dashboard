
# OPUS-MT dashboard

A web-interface for the exploring and comparing the performance of open machine translation models based on scores collected in the [OPUS-MT leader board repository](https://github.com/Helsinki-NLP/OPUS-MT-leaderboard).


![example barchart](img/barchart_medium.png)

The [live dashboard](https://opus.nlpl.eu/dashboard/) is available at https://opus.nlpl.eu/dashboard/


## Installation

The interface is based on a lightweight implementation in PHP. The setup only requires a web browser with PHP and GD extensions installed. Simply clone the repository and put the sub-diretory `web` into a location that can be accessed from the web and that allows the execution of PHP scripts. Data will be pulled automatically from the [OPUS-MT leader board repository](https://github.com/Helsinki-NLP/OPUS-MT-leaderboard) and the OPUS-MT object storage as needed. Temporary disk space will be used for caching files.


## Usage

TODO


## Setup for contributed translations

You need to use local files to enable user-contributed translations. The files are stored in your "local data home directory" (see `$local_datahome` in `web/functions.php`). Go to that directory and create the directory that will be used to store contributed translations, user-data and logfiles:


```
mkdir Contributed-MT-leaderboard-data
sudo chgrp www-data Contributed-MT-leaderboard-data
sudo chmod g+ws Contributed-MT-leaderboard-data
```

Note that you need to adjust the permissions so that the web server can access and write to that directory.
In the same directory, clone the repository of the leaderboard with contributed translations (this will take quite some time as the repository is big and contains MANY files):


```
git clone https://github.com/Helsinki-NLP/Contributed-MT-leaderboard.git
sudo chgrp -R www-data Contributed-MT-leaderboard
cd Contributed-MT-leaderboard
git submodule update --init --recursive --remote
sudo chmod -R g+wX models scores
sudo find models scores -type d -exec chmod g+s {} \;
```

Again, adjust the commands according to the permissions you need to set to enable your web-server to access and write to the leaderboard directories.


Install pre-requisites for evaluating translations:

```
sudo pip install sacrebleu[ja,ko]
sudo pip install unbabel-comet
sudo apt-get install zip
```


Setup a SLURM server to handle batch jobs:

```
sudo apt-get install slurmd slurm-client slurmctld
```

Create a SLURM configuration file with a single CPU default queue (this is important as we need to avoid racing conditions of simultaneous jobs that access the same files!), for example the following compute nodes in `/etc/slurm-llnl/slurm.conf` (with `<servername>` replaced by your server name, see `hostname`):

```
# COMPUTE NODES
NodeName=<servername> CPUs=1 State=UNKNOWN
PartitionName=standard Nodes=opus2020 Default=NO MaxTime=4320 State=UP
```

There are sample comfig files in `Contributed-MT-leaderboard/tools`. You can do:

```
cd Contributed-MT-leaderboard/tools
sudo mkdir -p /etc/slurm-llnl
sudo cp cgroup.conf /etc/slurm-llnl/
h=`hostname` && sudo cat slurm.conf | \
sed -e "s/REPLACE_SLURM_SERVER/$h/" \
    -e s/REPLACE_SLURM_NODES/$h/" \
    -e s/REPLACE_SLURM_SHORT_NODES/$h/" \
    -e s/REPLACE_SLURM_STANDARD_NODES/$h/" \
    -e s/REPLACE_SLURM_LONG_NODES/$h/" > /etc/slurm-llnl/slurm.conf
```

Start the server and compute node client on the same machine with

```
sudo service slurmctld restart
sudo service slurmd restart
```

You can verify that the server runs by typing `squeue`.