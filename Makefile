VERSION=1.4

all:
	true
clean:
	true
install:
	for i in /usr/share/phpreport/{/,css,images,include} ; \
	 do install -o www-data -g www-data -d $(DESTDIR)/$$i; done
	for i in *.php *.html *.dtd css/* images/* include/* ; \
	 do install -o www-data -g www-data $$i \
	 $(DESTDIR)/usr/share/phpreport/$$i; \
	 done
	for i in /usr/share/doc/phpreport-$(VERSION)/{/,sql} ; \
	 do install -o root -g root -d $(DESTDIR)/$$i; done
	for i in doc/* htaccess.desactivado share/* INSTALL LICENSE; \
	 do install -o root -g root $$i \
	 $(DESTDIR)/usr/share/doc/phpreport-$(VERSION)/ ; \
	 done
	for i in sql/* ; \
	 do install -o root -g root $$i \
	 $(DESTDIR)/usr/share/doc/phpreport-$(VERSION)/$$i ; \
	 done
	install -d $(DESTDIR)/etc/phpreport
	ln -s ../../usr/share/phpreport/include/config* $(DESTDIR)/etc/phpreport/
	install -d $(DESTDIR)/etc/apache/conf.d
	for i in /etc/apache/; \
	 do install -o root -g root conf.d/phpreport $(DESTDIR)/$$i/conf.d/phpreport; done		
uninstall:
	true
i18n-dev:
	for i in i18n/*.po ; \
	 do xgettext -L PHP --from-code UTF-8 -j -o $$i `find -name '*.php'`; \
	 done
