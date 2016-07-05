rm $1/app/code/community/Reviewscouk
rm $1/app/locale/en_GB/Reviewscouk_Reviews.csv
rm $1/app/locale/fr_FR/Reviewscouk_Reviews.csv
rm $1/app/etc/modules/Reviewscouk_Reviews.xml
rm $1/app/design/adminhtml/default/default/template/reviews
rm $1/app/design/adminhtml/default/default/layout/reviews_admin.xml
rm $1/app/design/frontend/base/default/layout/reviews_layout.xml
rm $1/app/design/frontend/base/default/template/reviews
rm $1/var/connect/reviewscouk_reviews.xml

pwd=$(pwd)

mkdir $1/app/locale/en_GB
mkdir $1/app/locale/fr_FR

ln -s $pwd/app/code/community/Reviewscouk/ $1/app/code/community/Reviewscouk
ln -s $pwd/app/locale/en_GB/Reviewscouk_Reviews.csv $1/app/locale/en_GB/Reviewscouk_Reviews.csv
ln -s $pwd/app/locale/fr_FR/Reviewscouk_Reviews.csv $1/app/locale/fr_FR/Reviewscouk_Reviews.csv
ln -s $pwd/app/etc/modules/Reviewscouk_Reviews.xml $1/app/etc/modules/Reviewscouk_Reviews.xml
ln -s $pwd/app/design/adminhtml/default/default/template/reviews $1/app/design/adminhtml/default/default/template/reviews
ln -s $pwd/app/design/adminhtml/default/default/layout/reviews_admin.xml $1/app/design/adminhtml/default/default/layout/reviews_admin.xml
ln -s $pwd/app/design/frontend/base/default/layout/reviews_layout.xml $1/app/design/frontend/base/default/layout/reviews_layout.xml
ln -s $pwd/app/design/frontend/base/default/template/reviews $1/app/design/frontend/base/default/template/reviews
mkdir $1/var/connect
ln -s $pwd/var/connect/reviewscouk_reviews.xml $1/var/connect/reviewscouk_reviews.xml
