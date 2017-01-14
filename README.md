

## fastcgi

下载[fastcgi](http://pkgs.fedoraproject.org/repo/pkgs/fcgi/fcgi-2.4.0.tar.gz/d15060a813b91383a9f3c66faf84867e/fcgi-2.4.0.tar.gz)

```shell
wget http://pkgs.fedoraproject.org/repo/pkgs/fcgi/fcgi-2.4.0.tar.gz/d15060a813b91383a9f3c66faf84867e/fcgi-2.4.0.tar.gz
tar xvfz fcgi-2.4.0.tar.gz 
cd fcgi-2.4.0 
./configure
make
make install
```
centos7下，`make`的时候有可能遇到如下错误：
```
fcgio.cpp:50:14: error: 'EOF' was not declared in this scope
```
需要在./include/fcgio.h的第33处插入如下内容：
```c
#include 'stdio.h'
```

## fcgiwrap

```shell
git clone https://github.com/gnosek/fcgiwrap --depth 1 -b master
cd fcgiwrap
autoreconf -i
./configure --prefix=/opt/fcgiwrap
make
install
```
centos7下，make的时候有可能遇到如下错误：
```
fcgiwrap.c:413: undefined reference to `rpl_malloc'
```
打开config.h.in，将117行的如下内容注释掉：
```c
//#undef malloc
```
然后重新configure，重新make，即可通过编译

### 启动

```
/opt/fcgiwrap/sbin/fcgiwrap -c 20 -s unix:/tmp/fcgi.socket &
```

运行时有可能出现错误：
```
error while loading shared libraries: libfcgi.so.0
```

编辑/etc/ld.conf.d/local.conf，加入/usr/lib，然后`cp /usr/local/lib/libfcgi.so.0 /usr/lib/`，然后运行命令`ldconfig`，即可正常运行
