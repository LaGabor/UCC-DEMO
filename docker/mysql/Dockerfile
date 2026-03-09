FROM mysql:8.4

COPY docker/mysql/docker-entrypoint-keyring.sh /usr/local/bin/docker-entrypoint-keyring.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-keyring.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-keyring.sh"]
CMD ["mysqld"]
