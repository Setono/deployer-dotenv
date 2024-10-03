FROM php:8.2-cli-alpine

RUN apk --update add --no-cache openssh bash git rsync \
    && rm -rf /var/cache/apk/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN ssh-keygen -A

COPY ssh/id_rsa.pub /root/.ssh/authorized_keys

EXPOSE 22
CMD ["/usr/sbin/sshd", "-D"]
