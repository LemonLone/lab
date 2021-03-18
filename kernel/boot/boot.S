.globl start
start:
	.code16
	cli
	cld

	xorw %ax, %ax
	movw %ax,%ds
    movw %ax,%es
    movw %ax,%gs
    movw %ax,%fs
    movw %ax,%ss

seta20.1:
	inb     $0x64,%al
	testb   $0x2,%al
	jnz     seta20.1

	movb    $0xd1,%al
	outb    %al,$0x64

seta20.2:
	inb     $0x64,%al
	testb   $0x2,%al
	jnz     seta20.2

	movb    $0xdf,%al
	outb    %al,$0x60

	lgdt    gdtdesc
	movl    %cr0, %eax
	orl     $0x1, %eax
	movl    %eax, %cr0

	ljmp    $0x8, $protcseg

	.code32
protcseg:
	movw    $0x10, %ax
	movw    %ax, %ds
	movw    %ax, %es
	movw    %ax, %fs
	movw    %ax, %gs
	movw    %ax, %ss

	movl    $start, %esp
	call	loader

spin:
	jmp	spin

gdt:
	# null seg
	.long 0x0, 0x0

	# code seg
	.word 0xffff 		# Segment Limit 15:00
	.word 0x0 			# Base Address 15:00
	.byte 0x0 			# Base 23:16
	.byte 0b10011010	# P=1, DPL=0, S=1, Type=1010=Execute/Read
	.byte 0b11001111 	# G=1, D/B=1, L=0, AVL=0, Seg.Limit.19:16
	.byte 0x0 			# Base 31:24

	# data seg
	.word 0xffff 		# Segment Limit 15:00
	.word 0x0 			# Base Address 15:00
	.byte 0x0 			# Base 23:16
	.byte 0b10010010	# P=1, DPL=0, S=1, Type=0010=Read/Write
	.byte 0b11001111 	# G=1, D/B=1, L=0, AVL=0, Seg.Limit.19:16
	.byte 0x0 			# Base 31:24

gdtdesc:
	.word   0x17
	.long   gdt
