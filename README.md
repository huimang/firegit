# Firegit

Firegit是一个基于php7的开源GIT管理系统。

## php
php ~ 7.0.0
```shell
$:php -v
PHP 7.0.14 (cli) (built: Dec 24 2016 21:42:15) ( NTS )
Copyright (c) 1997-2016 The PHP Group
Zend Engine v3.0.0, Copyright (c) 1998-2016 Zend Technologies
```
php 的配置项
```
./configure  --prefix=/opt/php --enable-mysqlnd --enable-fpm --enable-mbstring --enable-zip --with-zlib --with-curl --with-libdir=lib64 --with-pdo-mysql
```

## luajit
luajit ~ 2.0.0
```shell
wget http://luajit.org/download/LuaJIT-2.0.4.tar.gz
tar xvfz LuaJIT-2.0.4.tar.gz 
cd LuaJIT-2.0.4
make install PREFIX=/opt/luajit
```

## nginx
nginx ~ 1.10.0

```shell
$:nginx -v
nginx version: nginx/1.10.2
```

nginx配置如下：
```shell
--prefix=/opt/nginx/ --with-ld-opt=-Wl,-rpath,/opt/luajit/lib --add-module=../ngx_devel_kit-0.3.0/ --add-module=../lua-nginx-module-0.10.7
```

nginx 配置文件如下：
```nginx
#firegit
server {
    server_name firegit.com;
    listen      80;
    index       index.php;
    
    # 设置GIT库地址
    set         $repo_root /home/work/repos/;
    # 设置网站根目录
    set         $site_root /home/work/sites/firegit/;
    
    root        $site_root/public/;
    client_max_body_size 500m;
    add_header Access-Control-Allow-Origin *;

    
    location /dist/ {
        root    $site_root;
        expires 30d;
        access_log off;
    }
    location / {
        if (!-e $request_filename) {
            rewrite . /index.php$uri last;
        }
    }
    # 用来完成一些私有验证的方法
    location /git/ {
        #allow           127.0.0.1;
        #allow           192.168.0.0/16;
        #deny            all;

        fastcgi_pass    127.0.0.1:9000;
        fastcgi_param   SCRIPT_FILENAME $site_root/app$fastcgi_script_name;
        include         fastcgi_params;
    }

    location ~ ^/[^/]+\.(css|js|xml|ico) {
        expires 30d;
        access_log off;
    }
    
    location ~ ^/([^\/]+)/([^\.\/]+)(\.git)?/info/refs {
        set $git_group $1;
        set $git_name $2;
        access_by_lua_block {
            local res = ngx.location.capture("/git/auth.php", {args = {group = ngx.var.git_group, name = ngx.var.git_name}})
            ngx.log(ngx.NOTICE, "body=" .. res.body)
            ngx.log(ngx.NOTICE, "status=" .. res.status)
            if res.status == 200 then
                return
            end

            if res.status == 404 then
                ngx.exit(404)
            end
            
            -- 返回 HTTP 401 认证输入框
            ngx.header.www_authenticate = [[Basic realm="Restricted"]]
            ngx.exit(401)
        }
        include git.conf;    
    }

    location ~  ^/(.*)/(.*)(\.git)?/git-(receive|upload)-pack$ {
        set $git_group $1;
        set $git_name $2;
        include git.conf;
    }

   location /index.php {
        set $git_group "";
        set $git_name "";
        set $git_uri "";

        if ($uri ~ "^/index.php/(\w+)/(\w+)(/.*)$" ) {
            set $git_group $1;
            set $git_name $2;
            set $git_uri $3;
        }

        set $git_request_uri "";

        # 用来实现对网址的重写
        rewrite_by_lua_block {
            ngx.var.git_request_uri = ngx.var.request_uri
            ngx.log(ngx.NOTICE, "git_group:" .. ngx.var.git_group); 
            ngx.log(ngx.NOTICE, "git_name:" .. ngx.var.git_name);

            if "" ~= ngx.var.git_group and "" ~= ngx.var.git_name then
                local ret = ngx.location.capture('/git/exist.php', {args = {group = ngx.var.git_group, name = ngx.var.git_name}})
                ngx.log(ngx.NOTICE, "body:" .. ret.body)

                -- 如果不存在该git库，则返回yaf处理
                if ret.status == 404 then
                    return
                end

                -- 修改nginx的访问地址
                local newuri = "/repo" .. ngx.var.git_uri
                local newargs = ""
                if nil == ngx.var.query_string then
                    newargs = "repo_id=" .. ret.body
                else
                    newargs = "repo_id=" .. ret.body .. "&" .. ngx.var.query_string
                end
                ngx.var.git_request_uri = newuri .. "?" .. newargs
                ngx.req.set_uri("/index.php" .. newuri, false);
                ngx.req.set_uri_args(newargs)
            end
            ngx.log(ngx.NOTICE, "request_uri:" .. ngx.var.git_request_uri)
        }
        fastcgi_pass    127.0.0.1:9000;
        fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include         git_fastcgi_params;
        #fastcgi_param   REQUEST_URI     $update_request_uri;
    }
}
```

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