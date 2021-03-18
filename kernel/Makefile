# env configs
include env.mk

# dirs
BUILD_DIR := build
BOOT_BUILD_DIR := $(BUILD_DIR)/boot

# all
all: bootloader

# bootloader
bootloader: $(BOOT_BUILD_DIR)/bootloader.tmp
	objcopy -S -O binary -j .text $< $@
	chmod 755 bootloader
	perl boot/sign.pl bootloader
$(BOOT_BUILD_DIR)/bootloader.tmp: $(BOOT_BUILD_DIR)/boot.o $(BOOT_BUILD_DIR)/loader.o
	ld -N -m elf_i386 -e start -Ttext 0x7C00 $^ -o $@
$(BOOT_BUILD_DIR)/boot.o: boot/boot.S | $(BOOT_BUILD_DIR)
	as --32 -march=i386 $< -o $@
$(BOOT_BUILD_DIR)/loader.o: boot/loader.c
	gcc -std=gnu99 -m32 -static -Wall -Wno-format -Wno-unused -Werror -nostdinc -fno-builtin -fno-omit-frame-pointer -fno-tree-ch -fno-stack-protector -Os -c -o $@ $<
$(BOOT_BUILD_DIR):
	-mkdir $(BUILD_DIR)
	-mkdir $(BOOT_BUILD_DIR)

# clean
clean:
	-@rm -r bootloader $(BUILD_DIR)