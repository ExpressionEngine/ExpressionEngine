FROM node

WORKDIR /opt/app
COPY package.json /opt/app

RUN npm install
