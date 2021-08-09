export PATH := $(EXTRA_PATH):$(PATH)

REGISTRY := docker.io
VERSION := $(shell git describe --tags --always)
IMAGE := $(REGISTRY)/mojo:$(VERSION)

version:
	@echo $(VERSION)

build:
	docker build --build-arg BUILD_VERSION=$(VERSION) . -t $(IMAGE)
